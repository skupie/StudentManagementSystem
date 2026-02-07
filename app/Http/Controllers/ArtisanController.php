<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class ArtisanController extends Controller
{
    public function show()
    {
        return view('pages.artisan', [
            'commands' => $this->commandOptions(),
        ]);
    }

    public function run(Request $request)
    {
        $commands = $this->commandOptions();

        $request->validate([
            'pin' => ['required', 'string', 'max:50'],
            'command' => ['required', 'string', 'in:' . implode(',', array_keys($commands))],
        ]);

        if (! hash_equals($this->pinCode(), trim((string) $request->input('pin')))) {
            return back()
                ->withInput($request->only('command'))
                ->with('artisan_error', 'Invalid PIN. Please try again.');
        }

        $command = $request->input('command');
        $exitCode = 1;
        $output = '';

        try {
            if ($command === 'migrate') {
                $exitCode = Artisan::call('migrate', ['--force' => true]);
            } else {
                $exitCode = Artisan::call($command);
            }
            $output = Artisan::output();
        } catch (\Throwable $e) {
            $output = $e->getMessage();
        }

        return back()
            ->withInput($request->only('command'))
            ->with('artisan_status', $exitCode === 0 ? 'Command executed successfully.' : 'Command failed.')
            ->with('artisan_output', $output);
    }

    protected function commandOptions(): array
    {
        return [
            'migrate' => 'php artisan migrate',
            'migrate:fresh' => 'php artisan migrate:fresh',
            'cache:clear' => 'php artisan cache:clear',
            'cache:table' => 'php artisan cache:table',
            'config:clear' => 'php artisan config:clear',
            'view:clear' => 'php artisan view:clear',
            'route:clear' => 'php artisan route:clear',
        ];
    }

    protected function pinCode(): string
    {
        $cached = \Illuminate\Support\Facades\Cache::get('artisan_pin_override');
        if ($cached) {
            return (string) $cached;
        }

        return (string) config('app.artisan_pin', env('ARTISAN_PIN', '1234'));
    }
}
