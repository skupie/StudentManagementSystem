<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Output\ConsoleOutput;
use Laravel\Prompts\Prompt;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanController extends Controller
{
    public function show(Request $request)
    {
        $payload = null;
        if ($request->filled('result')) {
            $key = basename((string) $request->query('result'));
            $path = "artisan-results/{$key}.json";
            if (Storage::disk('local')->exists($path)) {
                $payload = json_decode(Storage::disk('local')->get($path), true) ?: [];
                Storage::disk('local')->delete($path);
            }
        }

        return view('pages.artisan', [
            'commands' => $this->commandOptions(),
            'selected_command' => $payload['command'] ?? null,
            'artisan_status' => $payload['status'] ?? null,
            'artisan_output' => $payload['output'] ?? null,
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
        $statusMessage = '';

        try {
            $buffer = new BufferedOutput();
            $promptBuffer = new BufferedConsoleOutput();
            Prompt::setOutput($promptBuffer);
            if ($command === 'migrate') {
                $exitCode = Artisan::call('migrate', ['--force' => true], $buffer);
            } else {
                $exitCode = Artisan::call($command, [], $buffer);
            }
            $consoleOutput = trim($buffer->fetch());
            $promptOutput = trim($promptBuffer->fetch());
            $fallbackOutput = trim(Artisan::output());
            $output = $consoleOutput !== '' ? $consoleOutput : ($promptOutput !== '' ? $promptOutput : $fallbackOutput);
            $statusMessage = $exitCode === 0 ? 'Command executed successfully.' : 'Command failed.';
        } catch (\Throwable $e) {
            $output = $e->getMessage();
            $statusMessage = 'Command failed.';
        } finally {
            Prompt::setOutput(new ConsoleOutput());
        }

        if ($command === 'optimize') {
            $key = (string) Str::uuid();
            Storage::disk('local')->put("artisan-results/{$key}.json", json_encode([
                'command' => $command,
                'status' => $statusMessage,
                'output' => $output,
            ], JSON_UNESCAPED_SLASHES));

            return response('', 302)
                ->header('Location', "/artisan?result={$key}");
        }

        return back()
            ->withInput($request->only('command'))
            ->with('artisan_status', $statusMessage)
            ->with('artisan_output', $output);
    }

    protected function commandOptions(): array
    {
        return [
            'migrate' => 'php artisan migrate',
            'migrate:fresh' => 'php artisan migrate:fresh',
            'optimize' => 'php artisan optimize',
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
