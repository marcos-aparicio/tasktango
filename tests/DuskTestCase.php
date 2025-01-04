<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    protected static $assetsBuilt = false;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (!self::$assetsBuilt) {
            if (is_file('public/hot')) {
                dd('You should stop the Vite dev server to test using Dusk.');
            }

            echo 'Compiling front-end assets.' . PHP_EOL;
            exec('npm run build');
            echo 'Front-end assets compiled.' . PHP_EOL;

            self::$assetsBuilt = true;
        }

        if (!static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)
            ->addArguments(
                collect([
                    $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-search-engine-choice-screen',
                ])->unless(
                    $this->hasHeadlessDisabled(), function (Collection $items) {
                        return $items->merge([
                            '--disable-gpu',
                            '--headless=new',
                        ]);
                    }
                )->all()
            );

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
