<?php

namespace SBOMinator\Laravel\Console;

use Illuminate\Console\Command;
use SBOMinator\Lib\Generator\CycloneDXSBOMGenerator;
use SBOMinator\Lib\Generator\SpdxSBOMGenerator;
use SBOMinator\Lib\Parser\ComposerParser;
use SBOMinator\Lib\Parser\NpmParser;

class SBOMinatorGenerateCommand extends Command
{
    protected $signature = 'sbominator:generate 
                            {--output=sbom.json : Output file path relative to base path}
                            {--format=cyclonedx : Output format (cyclonedx or spdx)}';

    protected $description = 'Generate SBOM (Software Bill of Materials) for your Laravel application';

    public function handle(): int
    {
        $composerLockFile = base_path('composer.lock');
        $format = strtolower($this->option('format'));
        $npmLockFile = base_path('package-lock.json');
        $outputFile = base_path($this->option('output'));

        if (!in_array($format, ['cyclonedx', 'spdx'])) {
            $this->error('Invalid format. Supported formats: cyclonedx, spdx');

            return Command::FAILURE;
        } elseif (!file_exists($composerLockFile)) {
            $this->error('composer.lock file not found. Please run `composer install` first.');

            return Command::FAILURE;
        } elseif (!is_readable($composerLockFile)) {
            $this->error('composer.lock file is not readable. Please check file permissions.');

            return Command::FAILURE;
        }

        $this->info('Parsing Composer dependencies...');
        $composerDependencies = (new ComposerParser())->loadFromFile($composerLockFile)->parseDependencies();
        $npmDependencies = [];

        if (file_exists($npmLockFile) && is_readable($npmLockFile)) {
            $this->info('Parsing NPM dependencies...');
            $npmDependencies = (new NpmParser())->loadFromFile($npmLockFile)->parseDependencies();
        }

        $this->info('Generating SBOM...');

        file_put_contents($outputFile, $this->generateSbom($format, array_merge($composerDependencies, $npmDependencies)));

        $this->info("SBOM file generated at: {$outputFile}");

        return Command::SUCCESS;
    }

    private function generateSbom(string $format, array $dependencies): string
    {
        return match ($format) {
            'cyclonedx' => (new CycloneDXSBOMGenerator($dependencies))->generate(),
            'spdx' => (new SpdxSBOMGenerator($dependencies))->generate(),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };
    }
}