<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;

class TrainModel extends Command
{
    protected $signature = 'ml:train';

    protected $description = 'Tahmin modelini eğitir ve çalışan ML servisine yükler';

    public function handle(): int
    {
        // ml klasörü backend'in yanında duruyor
        $mlDir = base_path('../ml');
        $python = PHP_OS_FAMILY === 'Windows'
            ? $mlDir . '/.venv/Scripts/python.exe'
            : $mlDir . '/.venv/bin/python';

        if (! file_exists($python)) {
            $this->error("Python bulunamadı: {$python}");
            $this->line('Önce ml klasöründe sanal ortamı (.venv) kur.');
            return self::FAILURE;
        }

        // 1) Modeli eğit
        $this->info('1/2 Model eğitiliyor...');
        $result = Process::path($mlDir)
            ->env(['PYTHONUTF8' => '1'])
            ->timeout(600)
            ->run([$python, '-m', 'src.train'], function (string $type, string $output) {
                $this->output->write($output);
            });

        if ($result->failed()) {
            $this->error('Eğitim başarısız oldu.');
            return self::FAILURE;
        }

        // 2) Çalışan servise yeni modeli yüklet
        $this->info('2/2 Yeni model servise yükleniyor...');
        try {
            $response = Http::timeout(30)->post(rtrim(config('services.ml.url'), '/') . '/reload');
        } catch (ConnectionException $e) {
            $this->warn('ML servisi çalışmıyor. Model kaydedildi, servis başlatılınca otomatik yüklenecek.');
            return self::SUCCESS;
        }

        if ($response->failed()) {
            $this->error('Servis modeli yükleyemedi: ' . ($response->json('detail') ?? $response->status()));
            return self::FAILURE;
        }

        $this->info('Tamam! Model: ' . $response->json('model') . ', veri: ' . $response->json('trained_until') . ' tarihine kadar');
        return self::SUCCESS;
    }
}