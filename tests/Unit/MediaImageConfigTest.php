<?php

namespace Tests\Unit;

use App\Helpers\Color;
use App\Services\MediaService\MediaImageConfig;
use App\Services\MediaService\TmpFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\DriverException;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class MediaImageConfigTest extends TestCase
{
    /**
     * @throws DriverException
     * @throws InvalidArgumentException
     * @return void
     */
    public function testGetDominantColorForSolidImage(): void
    {
        $file = new TmpFile(ImageManager::usingDriver(Driver::class)
            ->createImage(10, 10)
            ->fill('#ff0000')
            ->encodeUsingMediaType('image/png')
            ->toString());

        try {
            $sourcePath = $file->path();
            $this->assertNotNull($sourcePath);

            $dominantColor = (new class () extends MediaImageConfig {})->getDominantColor($sourcePath);

            $this->assertNotNull($dominantColor);

            $color = Color::createFromHex($dominantColor);

            $this->assertEqualsWithDelta(255, $color->red, 5);
            $this->assertEqualsWithDelta(0, $color->green, 5);
            $this->assertEqualsWithDelta(0, $color->blue, 5);
        } finally {
            $file->close();
        }
    }
}
