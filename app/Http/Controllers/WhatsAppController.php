<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kstmostofa\LaravelWhatsApp\Facades\WhatsApp;
use Kstmostofa\LaravelWhatsApp\Web\SidecarManager;
use Kstmostofa\LaravelWhatsApp\Exceptions\SidecarException;

class WhatsAppController extends Controller
{
    /**
     * Show the WhatsApp dashboard.
     */
    public function index(SidecarManager $manager)
    {
        $sidecarRunning = $this->isSidecarRunning($manager);
        $sessionStatus = 'disconnected';
        $qrCode = null;
        $info = null;
        $groups = [];
        $error = null;

        if ($sidecarRunning) {
            try {
                $session = WhatsApp::web('main');
                $state = $session->state();
                $sessionStatus = $state['status'] ?? 'unknown';

                if ($sessionStatus === 'qr' || $sessionStatus === 'initializing') {
                    try {
                        $qr = $session->qr();
                        $qrCode = $qr['qr'] ?? null;
                    } catch (\Exception $e) {
                        // QR not generated yet
                    }
                } elseif ($sessionStatus === 'ready' || $sessionStatus === 'authenticated') {
                    try {
                        $info = $session->info();
                    } catch (\Exception $e) {
                        // Info not available yet
                    }
                    if ($sessionStatus === 'ready') {
                        try {
                            $groups = $session->groups()->all();
                        } catch (\Exception $e) {
                            $groups = []; // Groups not available yet, silently skip
                        }
                    }
                }
            } catch (SidecarException $e) {
                if ($e->getCode() === 404 || str_contains($e->getMessage(), 'session not found')) {
                    $sessionStatus = 'disconnected';
                } else {
                    $sessionStatus = 'error';
                    $error = $e->getMessage();
                }
            } catch (\Exception $e) {
                $sessionStatus = 'error';
                $error = $e->getMessage();
            }
        }

        return view('whatsapp.dashboard', compact('sidecarRunning', 'sessionStatus', 'qrCode', 'info', 'error', 'groups'));
    }

    /**
     * Get the sidecar and session status in JSON format.
     */
    public function status(Request $request, SidecarManager $manager)
    {
        if ($request->hasSession()) {
            $request->session()->reflash();
        }

        $sidecarRunning = $this->isSidecarRunning($manager);
        $sessionStatus = 'disconnected';
        $qrCode = null;
        $info = null;
        $groups = [];
        $error = null;

        if ($sidecarRunning) {
            try {
                $session = WhatsApp::web('main');
                $state = $session->state();
                $sessionStatus = $state['status'] ?? 'unknown';

                if ($sessionStatus === 'qr' || $sessionStatus === 'initializing') {
                    try {
                        $qr = $session->qr();
                        $qrCode = $qr['qr'] ?? null;
                    } catch (\Exception $e) {
                        // QR code might not be generated yet
                    }
                } elseif ($sessionStatus === 'ready' || $sessionStatus === 'authenticated') {
                    try {
                        $info = $session->info();
                    } catch (\Exception $e) {
                        // Info not available yet
                    }
                    if ($sessionStatus === 'ready') {
                        try {
                            $groups = $session->groups()->all();
                        } catch (\Exception $e) {
                            $groups = []; // Groups not available yet, silently skip
                        }
                    }
                }
            } catch (SidecarException $e) {
                if ($e->getCode() === 404 || str_contains($e->getMessage(), 'session not found')) {
                    $sessionStatus = 'disconnected';
                } else {
                    $sessionStatus = 'error';
                    $error = $e->getMessage();
                }
            } catch (\Exception $e) {
                $sessionStatus = 'error';
                $error = $e->getMessage();
            }
        }

        return response()->json([
            'sidecarRunning' => $sidecarRunning,
            'sessionStatus' => $sessionStatus,
            'qrCode' => $qrCode,
            'info' => $info,
            'groups' => $groups,
            'error' => $error,
        ]);
    }

    /**
     * Start the Node.js sidecar service.
     */
    public function startSidecar(SidecarManager $manager)
    {
        try {
            $manager->start();
            return back()->with('success', 'WhatsApp sidecar server started successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start sidecar: ' . $e->getMessage());
        }
    }

    /**
     * Stop the Node.js sidecar service.
     */
    public function stopSidecar(SidecarManager $manager)
    {
        try {
            $manager->stop();
            return back()->with('success', 'WhatsApp sidecar server stopped.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to stop sidecar: ' . $e->getMessage());
        }
    }

    /**
     * Start the WhatsApp Web session.
     */
    public function startSession()
    {
        try {
            WhatsApp::web('main')->start();
            return back()->with('success', 'WhatsApp session initialized. Waiting for QR Code...');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start session: ' . $e->getMessage());
        }
    }

    /**
     * Destroy the WhatsApp Web session and clear authentication.
     */
    public function destroySession()
    {
        try {
            WhatsApp::web('main')->destroy();
            return back()->with('success', 'WhatsApp session logged out and cleared.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to clear session: ' . $e->getMessage());
        }
    }

    /**
     * Send a WhatsApp message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'phone'      => 'required|string|min:1',
            'message'    => 'nullable|string',
            'attachment' => 'nullable|file|max:15360', // 15MB limit
        ]);

        $to   = trim($request->input('phone'));
        $body = $request->input('message', '');

        if (empty($to)) {
            return back()->withErrors(['phone' => 'Nomor tujuan atau grup tidak boleh kosong.'])->withInput();
        }

        if (empty($body) && !$request->hasFile('attachment')) {
            return back()->withErrors(['message' => 'Pesan atau lampiran harus diisi.'])->withInput();
        }

        try {
            $session = WhatsApp::web('main');

            if ($request->hasFile('attachment')) {
                $file     = $request->file('attachment');
                $mimeType = $file->getMimeType();
                $filename = $file->getClientOriginalName();
                $base64   = base64_encode(file_get_contents($file->path()));

                $payload = [
                    'base64'   => $base64,
                    'mimeType' => $mimeType,
                    'filename' => $filename,
                ];

                if (!empty($body)) {
                    $payload['caption'] = $body;
                }

                if (str_starts_with($mimeType, 'image/')) {
                    $session->messages()->sendImage($to, $payload);
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $session->messages()->sendVideo($to, $payload);
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $session->messages()->sendAudio($to, $payload);
                } else {
                    $session->messages()->sendDocument($to, $payload);
                }
            } else {
                $session->messages()->sendText($to, $body);
            }

            return back()->with('success', 'Pesan berhasil dikirim!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim pesan: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Check if the sidecar service is running, with a socket fallback for Windows systems
     * where tasklist/process lookup permissions may be restricted.
     */
    protected function isSidecarRunning(SidecarManager $manager): bool
    {
        if ($manager->isRunning()) {
            return true;
        }

        $connection = @fsockopen(
            config('laravel-whatsapp.web.host', '127.0.0.1'),
            (int) config('laravel-whatsapp.web.port', 3000),
            $errno,
            $errstr,
            0.15
        );

        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }

        return false;
    }
}
