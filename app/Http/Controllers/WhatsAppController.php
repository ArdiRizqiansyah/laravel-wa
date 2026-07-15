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
        $sidecarRunning = $manager->isRunning();
        $sessionStatus = 'disconnected';
        $qrCode = null;
        $info = null;
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
                        // Info details not available yet
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

        return view('whatsapp.dashboard', compact('sidecarRunning', 'sessionStatus', 'qrCode', 'info', 'error'));
    }

    /**
     * Get the sidecar and session status in JSON format.
     */
    public function status(SidecarManager $manager)
    {
        $sidecarRunning = $manager->isRunning();
        $sessionStatus = 'disconnected';
        $qrCode = null;
        $info = null;
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
                        // Info details not available yet
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
            'error' => $error,
        ]);
    }

    /**
     * Start the Node.js sidecar service.
     */
    public function startSidecar(SidecarManager $manager)
    {
        try {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $node = config('laravel-whatsapp.web.sidecar.node_binary', 'node');
                $entry = config('laravel-whatsapp.web.sidecar.path') . DIRECTORY_SEPARATOR . 'index.js';
                
                // Quote paths to handle spaces on Windows
                $cmd = sprintf('start /B "" "%s" "%s"', $node, $entry);
                
                // Set environment variables for the process
                putenv("PORT=" . config('laravel-whatsapp.web.port', 3000));
                putenv("HOST=" . config('laravel-whatsapp.web.host', '127.0.0.1'));
                putenv("SIDECAR_TOKEN=" . config('laravel-whatsapp.web.token'));
                putenv("SESSION_DIR=" . config('laravel-whatsapp.web.sidecar.session_dir'));
                putenv("SIDECAR_PID_FILE=" . config('laravel-whatsapp.web.sidecar.pid_file'));
                putenv("AUTO_START_SESSIONS=" . (config('laravel-whatsapp.web.sidecar.auto_start_sessions', true) ? 'true' : 'false'));
                
                $handle = popen($cmd, "r");
                if ($handle) {
                    pclose($handle);
                }
                
                // Wait up to 2 seconds for the PID file to be written by Node
                $pidFile = config('laravel-whatsapp.web.sidecar.pid_file');
                for ($i = 0; $i < 20; $i++) {
                    usleep(100000);
                    if (is_file($pidFile)) {
                        break;
                    }
                }
            } else {
                $manager->start();
            }
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
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $to = $request->input('phone');
        $body = $request->input('message');

        try {
            WhatsApp::web('main')->messages()->sendText($to, $body);
            return back()->with('success', 'Message sent successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to send message: ' . $e->getMessage());
        }
    }
}
