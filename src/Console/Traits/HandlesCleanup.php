<?php namespace Winter\Storm\Console\Traits;

/**
 * Console Command Trait that injects cross-platform signal handling to trigger
 * cleanup on exit through the handleCleanup() method on the implementing class.
 *
 * @package winter\storm
 * @author Luke Towers
 */
trait HandlesCleanup
{
    /**
     * Returns the process signals this command listens to
     * @see https://www.php.net/manual/en/pcntl.constants.php
     * Used to support the handleCleanup() end-class method
     */
    public function getSubscribedSignals(): array
    {
        $signals = [];
        if (method_exists($this, 'handleCleanup')) {
            // Handle Windows OS
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Attach to Windows Ctrl+C & Ctrl+Break events
                if (function_exists('sapi_windows_set_ctrl_handler')) {
                    sapi_windows_set_ctrl_handler([$this, 'handleWindowsSignal'], true);
                }
            // Handle Unix-like OS
            } else {
                $signals = [SIGINT, SIGTERM, SIGQUIT];
            }
        }

        return $signals;
    }

    /**
     * Handle the provided Unix process signal
     */
    public function handleSignal(int $signal): void
    {
        // Handle the signal
        if (method_exists($this, 'handleCleanup')) {
            $this->handleCleanup();
        }

        // Exit cleanly at this point if this was a user termination
        if (in_array($signal, [SIGINT, SIGQUIT])) {
            exit(0);
        }
    }

    /**
     * Handle the provided Windows process singal.
     */
    public function handleWindowsSignal(int $event): void
    {
        // Remove the handler
        sapi_windows_set_ctrl_handler([$this, 'handleWindowsSignal'], false);

        // Handle the signal
        if (
            method_exists($this, 'handleCleanup')
            && (
                $event === PHP_WINDOWS_EVENT_CTRL_C
                || $event === PHP_WINDOWS_EVENT_CTRL_BREAK
            )
        ) {
            $this->handleCleanup();

            // Exit cleanly at this point if this was a user termination
            exit(0);
        }
    }
}