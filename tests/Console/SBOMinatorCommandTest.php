<?php

namespace SBOMinator\Laravel\Tests\Console;

use Orchestra\Testbench\TestCase;
use SBOMinator\Laravel\SBOMinatorServiceProvider;

class SBOMinatorCommandTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SBOMinatorServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure test files don't exist at the start
        $this->cleanupTestFiles();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->cleanupTestFiles();

        parent::tearDown();
    }

    protected function cleanupTestFiles(): void
    {
        $filesToDelete = [
            base_path('composer.lock'),
            base_path('package-lock.json'),
            base_path('sbom.json'),
            base_path('custom-sbom.json')
        ];

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Create mock composer.lock file for testing
     */
    protected function createMockComposerLock(): string
    {
        $composerLockPath = base_path('composer.lock');

        $mockData = [
            'content-hash' => 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6', // Required by the parser
            'packages' => [
                [
                    'name' => 'laravel/framework',
                    'version' => '10.0.0',
                    'description' => 'The Laravel Framework.',
                    'license' => 'MIT',
                    'source' => [
                        'type' => 'git',
                        'url' => 'https://github.com/laravel/framework.git',
                        'reference' => 'a1b2c3d4e5f6'
                    ]
                ],
                [
                    'name' => 'symfony/http-foundation',
                    'version' => '6.0.0',
                    'description' => 'Symfony HttpFoundation Component',
                    'license' => ['MIT'],
                    'source' => [
                        'type' => 'git',
                        'url' => 'https://github.com/symfony/http-foundation.git',
                        'reference' => 'b1c2d3e4f5'
                    ]
                ]
            ],
            'packages-dev' => [
                [
                    'name' => 'phpunit/phpunit',
                    'version' => '10.0.0',
                    'description' => 'PHPUnit is a programmer-oriented testing framework for PHP.',
                    'license' => 'BSD-3-Clause',
                    'source' => [
                        'type' => 'git',
                        'url' => 'https://github.com/phpunit/phpunit.git',
                        'reference' => 'f7g8h9i0j1'
                    ]
                ]
            ]
        ];

        file_put_contents($composerLockPath, json_encode($mockData, JSON_PRETTY_PRINT));

        return $composerLockPath;
    }

    /**
     * Create mock package-lock.json file for testing
     */
    protected function createMockNpmLock(): string
    {
        $npmLockPath = base_path('package-lock.json');

        $mockData = [
            'name' => 'test-project',
            'version' => '1.0.0',
            'lockfileVersion' => 2,
            'requires' => true,
            'packages' => [
                '' => [
                    'name' => 'test-project',
                    'version' => '1.0.0',
                    'dependencies' => [
                        'vue' => '^3.2.0',
                        'axios' => '^1.0.0'
                    ]
                ],
                'node_modules/vue' => [
                    'version' => '3.2.45',
                    'resolved' => 'https://registry.npmjs.org/vue/-/vue-3.2.45.tgz',
                    'integrity' => 'sha512-9Nx/Mg2b2xWlXykmCwiTUCWHbWIj53bnkizBxKai1g61f2Xit700A1ljowpTIM11e3uipOeiPcSqnmBg6gyiaA=='
                ],
                'node_modules/axios' => [
                    'version' => '1.2.0',
                    'resolved' => 'https://registry.npmjs.org/axios/-/axios-1.2.0.tgz',
                    'integrity' => 'sha512-zT7wZyNYu3N5Bu0wuZ6QccIf93Qk1eV8LOewxgjOZFd2DenOs98cJ7+Y6703d0wkaXGY6/nZd4EweJaHz9uzQw=='
                ]
            ]
        ];

        file_put_contents($npmLockPath, json_encode($mockData, JSON_PRETTY_PRINT));

        return $npmLockPath;
    }

    public function testCommandWithNoComposerLock(): void
    {
        // Ensure no composer.lock exists
        if (file_exists(base_path('composer.lock'))) {
            unlink(base_path('composer.lock'));
        }

        $this->artisan('sbominator:generate')
            ->expectsOutput('composer.lock file not found. Please run `composer install` first.')
            ->assertExitCode(1);

        // Verify no output file was created
        $this->assertFileDoesNotExist(base_path('sbom.json'));
    }

    public function testCommandWithUnreadableComposerLock(): void
    {
        // Skip this test on Windows as it's hard to simulate permission issues
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->markTestSkipped('Skipping permission test on Windows');
            return;
        }

        // Create mock composer.lock
        $composerLockPath = $this->createMockComposerLock();

        try {
            // Make it unreadable
            chmod($composerLockPath, 0000);

            $this->artisan('sbominator:generate')
                ->expectsOutput('composer.lock file is not readable. Please check file permissions.')
                ->assertExitCode(1);

            // Verify no output file was created
            $this->assertFileDoesNotExist(base_path('sbom.json'));
        } finally {
            // Restore permissions so tearDown can delete it
            chmod($composerLockPath, 0644);
        }
    }

    public function testInvalidFormat(): void
    {
        // Create mock composer.lock
        $this->createMockComposerLock();

        $this->artisan('sbominator:generate', ['--format' => 'invalid'])
            ->expectsOutput('Invalid format. Supported formats: cyclonedx, spdx')
            ->assertExitCode(1);

        // Verify no output file was created
        $this->assertFileDoesNotExist(base_path('sbom.json'));
    }

    public function testDefaultOutputPathWithCycloneDX(): void
    {
        // Create mock files
        $this->createMockComposerLock();

        $this->artisan('sbominator:generate')
             ->expectsOutput('Parsing Composer dependencies...')
             ->expectsOutput('Generating SBOM...')
             ->expectsOutput('SBOM file generated at: ' . base_path('sbom.json'))
             ->assertExitCode(0);

        // Verify the file was created
        $this->assertFileExists(base_path('sbom.json'));

        // Verify the file is valid JSON with basic CycloneDX structure
        $content = file_get_contents(base_path('sbom.json'));
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('bomFormat', $data);
        $this->assertEquals('CycloneDX', $data['bomFormat']);

        // Verify components section contains the packages from our mock composer.lock
        $this->assertArrayHasKey('components', $data);
        $this->assertIsArray($data['components']);

        // Check if at least one of our mock dependencies is included
        $foundLaravel = false;
        foreach ($data['components'] as $component) {
            if (isset($component['name']) && $component['name'] === 'laravel/framework') {
                $foundLaravel = true;
                $this->assertEquals('10.0.0', $component['version']);
                break;
            }
        }

        $this->assertTrue($foundLaravel, 'Expected to find laravel/framework component in SBOM');
    }

    public function testOutputWithSpdxFormat(): void
    {
        // Create mock files
        $this->createMockComposerLock();

        $this->artisan('sbominator:generate', ['--format' => 'spdx'])
             ->expectsOutput('Parsing Composer dependencies...')
             ->expectsOutput('Generating SBOM...')
             ->expectsOutput('SBOM file generated at: ' . base_path('sbom.json'))
             ->assertExitCode(0);

        // Verify the file was created
        $this->assertFileExists(base_path('sbom.json'));

        // Verify the file is valid JSON with basic SPDX structure
        $content = file_get_contents(base_path('sbom.json'));
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('spdxVersion', $data);

        // SPDX format has packages in a packages array
        $this->assertArrayHasKey('packages', $data);
        $this->assertIsArray($data['packages']);

        // Check if at least one of our mock dependencies is included
        $foundLaravel = false;
        foreach ($data['packages'] as $package) {
            if (isset($package['name']) && $package['name'] === 'laravel/framework') {
                $foundLaravel = true;
                $this->assertEquals('10.0.0', $package['versionInfo']);
                break;
            }
        }

        $this->assertTrue($foundLaravel, 'Expected to find laravel/framework package in SPDX SBOM');
    }

    public function testCustomOutputPath(): void
    {
        // Create mock files
        $this->createMockComposerLock();
        $customPath = 'custom-sbom.json';
        $fullCustomPath = base_path($customPath);

        $this->artisan('sbominator:generate', ['--output' => $customPath])
             ->expectsOutput('Parsing Composer dependencies...')
             ->expectsOutput('Generating SBOM...')
             ->expectsOutput('SBOM file generated at: ' . $fullCustomPath)
             ->assertExitCode(0);

        // Verify the file was created at the custom path
        $this->assertFileExists($fullCustomPath);
        $this->assertFileDoesNotExist(base_path('sbom.json'));

        // Verify the file is valid JSON with basic CycloneDX structure
        $content = file_get_contents($fullCustomPath);
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('bomFormat', $data);
    }

    public function testComposerAndNpmIntegration(): void
    {
        // Create mock files
        $this->createMockComposerLock();
        $this->createMockNpmLock();

        $this->artisan('sbominator:generate')
            ->expectsOutput('Parsing Composer dependencies...')
            ->expectsOutput('Parsing NPM dependencies...')
            ->expectsOutput('Generating SBOM...')
            ->expectsOutput('SBOM file generated at: '.base_path('sbom.json'))
            ->assertExitCode(0);

        // Verify the file was created
        $this->assertFileExists(base_path('sbom.json'));

        // Verify the file is valid JSON with basic CycloneDX structure
        $content = file_get_contents(base_path('sbom.json'));
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('bomFormat', $data);

        // Verify components section contains packages from both npm and composer
        $this->assertArrayHasKey('components', $data);
        $this->assertIsArray($data['components']);

        // Check if dependencies from both sources are included
        $foundLaravel = false;
        $foundVue = false;

        foreach ($data['components'] as $component) {
            if (isset($component['name'])) {
                if ($component['name'] === 'laravel/framework') {
                    $foundLaravel = true;
                } elseif ($component['name'] === 'vue' || strpos($component['name'], 'vue') !== false) {
                    $foundVue = true;
                }
            }
        }

        $this->assertTrue($foundLaravel, 'Expected to find Laravel component in SBOM');
        $this->assertTrue($foundVue, 'Expected to find Vue component in SBOM');
    }
}