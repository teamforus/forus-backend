<?php

namespace Tests\Unit;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Data\PdfToImgPageData;
use App\Services\PdfToImgService\Data\PdfToImgRequestData;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use App\Services\PdfToImgService\Implementations\AwsLambdaPdfToImgConverter;
use App\Services\PdfToImgService\Implementations\LocalPopplerPdfToImgConverter;
use App\Services\PdfToImgService\PdfToImgService;
use App\Services\PdfToImgService\PdfToImgServiceProvider;
use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Aws\Lambda\LambdaClient;
use Aws\MockHandler;
use Aws\Result;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\Fakes\FakePdfToImgConverter;
use Tests\TestCase;
use Tests\Traits\UsesLocalPoppler;
use Tests\Traits\UsesPdfFixtures;

class PdfToImgServiceTest extends TestCase
{
    use UsesLocalPoppler;
    use UsesPdfFixtures;

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testRequestDataCanUseConfiguredDefaults(): void
    {
        Config::set('forus.pdf_to_img.defaults', [
            'max_pages' => 8,
            'dpi' => 120,
            'quality' => 74,
            'max_width' => 1200,
            'max_height' => 1600,
            'oversize' => 'error',
            'strict_page_validation' => true,
        ]);

        $request = PdfToImgRequestData::fromConfig('pdf-bytes');

        $this->assertSame('pdf-bytes', $request->getPdf());
        $this->assertNull($request->getPages());
        $this->assertSame(8, $request->getMaxPages());
        $this->assertSame(120, $request->getDpi());
        $this->assertSame(74, $request->getQuality());
        $this->assertSame(1200, $request->getMaxWidth());
        $this->assertSame(1600, $request->getMaxHeight());
        $this->assertSame('error', $request->getOversize());
        $this->assertTrue($request->getStrictPageValidation());
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testRequestDataNormalizesSingleMaxDimension(): void
    {
        $request = (new PdfToImgRequestData(pdf: 'pdf-bytes', maxWidth: 1600))->normalize();

        $this->assertSame(1600, $request->getMaxWidth());
        $this->assertSame(1600, $request->getMaxHeight());

        $request = (new PdfToImgRequestData(pdf: 'pdf-bytes', maxHeight: 1200))->normalize();

        $this->assertSame(1200, $request->getMaxWidth());
        $this->assertSame(1200, $request->getMaxHeight());
    }

    /**
     * @return void
     */
    public function testRequestDataRejectsInvalidOptions(): void
    {
        $this->assertRequestNormalizationFails(
            new PdfToImgRequestData(pdf: 'pdf-bytes', dpi: 0),
            'dpi must be a positive integer.',
        );
        $this->assertRequestNormalizationFails(
            new PdfToImgRequestData(pdf: 'pdf-bytes', quality: 101),
            'quality must be less than or equal to 100.',
        );
        $this->assertRequestNormalizationFails(
            new PdfToImgRequestData(pdf: 'pdf-bytes', oversize: 'ignore'),
            'oversize must be either "scale" or "error".',
        );
    }

    /**
     * @throws BindingResolutionException
     * @return void
     */
    public function testPdfToImgServiceProviderResolvesAwsConnectionThroughContract(): void
    {
        $this->configureS3PdfToImgDisk();
        $this->setPdfToImgConnection('aws');

        $this->assertInstanceOf(AwsLambdaPdfToImgConverter::class, $this->app->make(PdfToImgConverterContract::class));
    }

    /**
     * @throws BindingResolutionException
     * @return void
     */
    public function testPdfToImgServiceProviderResolvesLocalConnectionThroughContract(): void
    {
        $this->setPdfToImgConnection('local');

        $this->assertInstanceOf(
            LocalPopplerPdfToImgConverter::class,
            $this->app->make(PdfToImgConverterContract::class),
        );
    }

    /**
     * @throws BindingResolutionException
     * @return void
     */
    public function testPdfToImgServiceProviderRejectsLocalConnectionInProduction(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->setPdfToImgConnection('local');

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('Local PDF to image converter cannot be used in production.');

        $this->app->make(PdfToImgConverterContract::class);
    }

    /**
     * @return void
     */
    public function testAwsCredentialsUseExplicitConnectionCredentials(): void
    {
        $credentials = $this->makeProviderCredentials([
            'credentials' => [
                'key' => 'service-key',
                'secret' => 'service-secret',
                'token' => 'service-token',
            ],
        ]);

        $this->assertInstanceOf(Credentials::class, $credentials);
        $this->assertSame('service-key', $credentials->getAccessKeyId());
        $this->assertSame('service-secret', $credentials->getSecretKey());
        $this->assertSame('service-token', $credentials->getSecurityToken());
    }

    /**
     * @return void
     */
    public function testAwsCredentialsFallbackToSdkProviderWhenConnectionCredentialsAreMissing(): void
    {
        $this->assertIsCallable($this->makeProviderCredentials(['credentials' => []]));
    }

    /**
     * @return void
     */
    public function testAwsCredentialsRejectPartialConnectionCredentials(): void
    {
        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('PDF to image AWS credentials require both key and secret.');

        $this->makeProviderCredentials(['credentials' => ['key' => 'service-key']]);
    }

    /**
     * @return void
     */
    public function testPdfToImgServiceLogsConverterFailuresWithoutPdfBytes(): void
    {
        $this->setPdfToImgConnection('aws');

        Config::set('forus.pdf_to_img.connections.aws.lambda.function_name', 'pdf-to-img-lambda-service');
        Config::set('forus.pdf_to_img.connections.aws.lambda.qualifier', 'prod');
        Config::set('forus.pdf_to_img.connections.aws.storage.disk', 's3_pdf_to_img');
        Config::set('forus.pdf_to_img.log_channel', 'custom_pdf_to_img');

        $exception = new PdfToImgException('Conversion failed.');
        $converter = (new FakePdfToImgConverter())->setException($exception);
        $logger = Mockery::mock();
        $loggedMessage = null;

        $logger
            ->shouldReceive('error')
            ->once()
            ->with(Mockery::on(function (string $message) use (&$loggedMessage) {
                $loggedMessage = $message;

                return str_contains($message, 'PDF to image conversion failed.') &&
                    str_contains($message, 'Conversion failed.') &&
                    str_contains($message, '"connection": "aws"') &&
                    str_contains($message, '"driver": "aws"') &&
                    str_contains($message, '"function_name": "pdf-to-img-lambda-service"') &&
                    str_contains($message, '"qualifier": "prod"') &&
                    str_contains($message, '"storage_disk": "s3_pdf_to_img"') &&
                    str_contains($message, '"pages": "1"') &&
                    str_contains($message, '"max_pages": 15') &&
                    str_contains($message, '"dpi": 150') &&
                    str_contains($message, '"quality": 85') &&
                    str_contains($message, '"max_width": 3000') &&
                    str_contains($message, '"max_height": 3000') &&
                    str_contains($message, '"oversize": "scale"') &&
                    str_contains($message, '"strict_page_validation": false') &&
                    !str_contains($message, 'secret-pdf-bytes');
            }));

        Log::shouldReceive('channel')->once()->with('custom_pdf_to_img')->andReturn($logger);

        try {
            (new PdfToImgService($converter))->convert(new PdfToImgRequestData(
                pdf: 'secret-pdf-bytes',
                pages: '1',
                maxPages: 15,
                dpi: 150,
                quality: 85,
                maxWidth: 3000,
                maxHeight: 3000,
                oversize: 'scale',
                strictPageValidation: false,
            ));

            $this->fail('The converter exception should be rethrown.');
        } catch (PdfToImgException $e) {
            $this->assertSame($exception, $e);
        }

        $this->assertStringNotContainsString('secret-pdf-bytes', $loggedMessage);
    }

    /**
     * @throws BindingResolutionException
     * @return void
     */
    public function testPdfToImgServiceProviderRejectsUnknownConnection(): void
    {
        $this->setPdfToImgConnection('unknown');

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('PDF to image converter connection [unknown] is not configured.');

        $this->app->make(PdfToImgConverterContract::class);
    }

    /**
     * @throws BindingResolutionException
     * @return void
     */
    public function testPdfToImgServiceProviderRejectsUnknownConnectionDriver(): void
    {
        $this->setPdfToImgConnection('unknown', ['driver' => 'unknown']);

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('Unsupported PDF to image converter driver [unknown].');

        $this->app->make(PdfToImgConverterContract::class);
    }

    /**
     * @return void
     */
    public function testAwsDriverSendsS3ReferencesDownloadsOutputAndCleansScratchFiles(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 12:00:00'));
        Storage::fake('s3_pdf_to_img');

        $outputKey = 'pdf-to-img/local/output/2026/07/07/conversion-1/page-0001.jpg';
        Storage::disk('s3_pdf_to_img')->put($outputKey, 'jpeg-bytes');

        [$converter, $mock] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'Payload' => json_encode($this->makeLambdaSuccessPayload($outputKey)),
        ]));

        $response = $converter->convert($this->makeConverterRequest());
        $page = $response->getPages()[0];
        $payload = json_decode($mock->getLastCommand()['Payload'], true);

        $this->assertSame(2, $response->getPageCount());
        $this->assertSame(1, $response->getRenderedCount());
        $this->assertSame('jpeg-bytes', $page->getImage());
        $this->assertSame('pdf-to-img-lambda-service', $mock->getLastCommand()['FunctionName']);
        $this->assertSame('RequestResponse', $mock->getLastCommand()['InvocationType']);
        $this->assertSame('prod', $mock->getLastCommand()['Qualifier']);
        $this->assertSame('conversion-1', $payload['requestId']);
        $this->assertSame('pdf-bucket', $payload['input']['bucket']);
        $this->assertSame('pdf-bucket', $payload['output']['bucket']);
        $this->assertSame('pdf-to-img/local/input/2026/07/07/conversion-1/source.pdf', $payload['input']['key']);
        $this->assertSame('pdf-to-img/local/output/2026/07/07/conversion-1', $payload['output']['prefix']);
        $this->assertSame('1', $payload['options']['pages']);
        $this->assertSame(15, $payload['options']['maxPages']);
        $this->assertSame(150, $payload['options']['dpi']);
        $this->assertSame(85, $payload['options']['quality']);
        $this->assertSame(3000, $payload['options']['maxWidth']);
        $this->assertSame(3000, $payload['options']['maxHeight']);
        $this->assertSame('scale', $payload['options']['oversize']);
        $this->assertFalse($payload['options']['strictPageValidation']);

        Storage::disk('s3_pdf_to_img')->assertMissing('pdf-to-img/local/input/2026/07/07/conversion-1/source.pdf');
        Storage::disk('s3_pdf_to_img')->assertMissing($outputKey);
    }

    /**
     * @return void
     */
    public function testAwsDriverKeepsScratchFilesWhenCleanupIsDisabled(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 12:00:00'));
        Storage::fake('s3_pdf_to_img');

        $outputKey = 'pdf-to-img/local/output/2026/07/07/conversion-1/page-0001.jpg';
        Storage::disk('s3_pdf_to_img')->put($outputKey, 'jpeg-bytes');

        [$converter] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'Payload' => json_encode($this->makeLambdaSuccessPayload($outputKey)),
        ]), cleanup: false);

        $converter->convert($this->makeConverterRequest());

        Storage::disk('s3_pdf_to_img')->assertExists('pdf-to-img/local/input/2026/07/07/conversion-1/source.pdf');
        Storage::disk('s3_pdf_to_img')->assertExists($outputKey);
    }

    /**
     * @return void
     */
    public function testAwsDriverRejectsMalformedPayloads(): void
    {
        Storage::fake('s3_pdf_to_img');

        [$converter] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'Payload' => 'not-json',
        ]));

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('PDF to image converter returned malformed JSON.');

        $converter->convert($this->makeConverterRequest());
    }

    /**
     * @return void
     */
    public function testAwsDriverRejectsValidationPayloads(): void
    {
        Storage::fake('s3_pdf_to_img');

        [$converter] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'Payload' => json_encode([
                'statusCode' => 400,
                'error' => [
                    'code' => PdfToImgException::ERROR_MAX_PAGES_EXCEEDED,
                    'message' => 'PDF page count 16 exceeds maxPages 15.',
                    'params' => [
                        'pageCount' => 16,
                        'maxPages' => 15,
                    ],
                ],
            ]),
        ]));

        try {
            $converter->convert($this->makeConverterRequest());

            $this->fail('The converter validation failure should be rethrown.');
        } catch (PdfToImgException $e) {
            $this->assertStringContainsString('PDF page count 16 exceeds maxPages 15.', $e->getMessage());
            $this->assertSame(PdfToImgException::ERROR_MAX_PAGES_EXCEEDED, $e->getErrorCode());
            $this->assertSame([
                'pageCount' => 16,
                'maxPages' => 15,
            ], $e->getErrorParams());
        }
    }

    /**
     * @return void
     */
    public function testAwsDriverRejectsMissingRenderedImageData(): void
    {
        Storage::fake('s3_pdf_to_img');

        [$converter] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'Payload' => json_encode($this->makeLambdaSuccessPayload('missing/page.jpg')),
        ]));

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('Failed to read rendered PDF page image.');

        $converter->convert($this->makeConverterRequest());
    }

    /**
     * @return void
     */
    public function testAwsDriverAddsScratchDetailsToFailures(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-07 12:00:00'));
        Storage::fake('s3_pdf_to_img');

        [$converter] = $this->makeAwsConverter(new Result([
            '@metadata' => [],
            'StatusCode' => 200,
            'FunctionError' => 'Unhandled',
            'Payload' => json_encode(['errorMessage' => 'Lambda crashed.']),
        ]));

        try {
            $converter->convert($this->makeConverterRequest());

            $this->fail('The converter failure should be rethrown.');
        } catch (PdfToImgException $e) {
            $this->assertStringContainsString('Lambda crashed.', $e->getMessage());
            $this->assertStringContainsString('request_id=conversion-1', $e->getMessage());
            $this->assertStringContainsString(
                'input_key=pdf-to-img/local/input/2026/07/07/conversion-1/source.pdf',
                $e->getMessage(),
            );
            $this->assertStringContainsString(
                'output_prefix=pdf-to-img/local/output/2026/07/07/conversion-1',
                $e->getMessage(),
            );
        }
    }

    /**
     * @return void
     */
    public function testLocalDriverRejectsNonLocalDisks(): void
    {
        Config::set('filesystems.disks.s3_pdf_to_img.driver', 's3');

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('Local PDF to image converter disk [s3_pdf_to_img] must use the local driver.');

        (new LocalPopplerPdfToImgConverter('s3_pdf_to_img', 'pdf-to-img/tmp'))->convert($this->makeConverterRequest());
    }

    /**
     * @return void
     */
    public function testLocalDriverReportsMissingPopplerBinaries(): void
    {
        Storage::fake('local');

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('missing-pdfinfo-binary');

        (new LocalPopplerPdfToImgConverter(
            disk: 'local',
            path: 'pdf-to-img/tmp',
            pdfinfoBinary: 'missing-pdfinfo-binary',
            timeout: 1,
        ))->convert($this->makeConverterRequest());
    }

    /**
     * @return void
     */
    public function testLocalPopplerDriverReportsMissingPdftoppmBinary(): void
    {
        Storage::fake('local');

        $this->expectException(PdfToImgException::class);
        $this->expectExceptionMessage('missing-pdftoppm-binary');

        $this->makeLocalPopplerConverterWithMissingPdftoppm()->convert($this->makeConverterRequest());
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testLocalPopplerDriverCleansTempDirectoryAfterSuccess(): void
    {
        Storage::fake('local');

        $response = $this->makeLocalPopplerConverterThatRendersPage()->convert($this->makeConverterRequest());

        $this->assertSame(1, $response->getRenderedCount());
        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testLocalPopplerDriverReportsScaledOutputDimensions(): void
    {
        Storage::fake('local');

        $response = $this->makeLocalPopplerConverterThatRendersPage()->convert(new PdfToImgRequestData(
            pdf: 'pdf-bytes',
            pages: '1',
            maxPages: 15,
            dpi: 300,
            quality: 85,
            maxWidth: 1000,
            maxHeight: 1000,
            oversize: 'scale',
            strictPageValidation: false,
        ));

        $this->assertSame([
            'Page 1 was scaled from 2480x3509 to 703x995 to fit within 1000x1000.',
        ], $response->getWarnings());
        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @return void
     */
    public function testLocalPopplerDriverCleansTempDirectoryAfterConversionFailure(): void
    {
        Storage::fake('local');

        try {
            $this->makeLocalPopplerConverterThatFailsDuringRender()->convert($this->makeConverterRequest());

            $this->fail('The converter failure should be rethrown.');
        } catch (PdfToImgException $e) {
            $this->assertSame('Render failed.', $e->getMessage());
        }

        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @return void
     */
    public function testLocalPopplerDriverRejectsPdfsOverMaxPagesWithValidationCode(): void
    {
        Storage::fake('local');

        try {
            $this->makeLocalPopplerConverterThatRendersPage(2)->convert(new PdfToImgRequestData(
                pdf: 'pdf-bytes',
                maxPages: 1,
                dpi: 150,
                quality: 85,
                maxWidth: 3000,
                maxHeight: 3000,
                oversize: 'scale',
                strictPageValidation: false,
            ));

            $this->fail('The converter validation failure should be rethrown.');
        } catch (PdfToImgException $e) {
            $this->assertSame('PDF page count 2 exceeds maxPages 1.', $e->getMessage());
            $this->assertSame(PdfToImgException::ERROR_MAX_PAGES_EXCEEDED, $e->getErrorCode());
            $this->assertSame([
                'pageCount' => 2,
                'maxPages' => 1,
            ], $e->getErrorParams());
        }

        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testLocalPopplerIntegrationConvertsOnePagePdfWhenBinariesAreAvailable(): void
    {
        $this->skipIfLocalPopplerUnavailable();
        Storage::fake('local');

        $response = (new LocalPopplerPdfToImgConverter('local', 'pdf-to-img/tmp'))->convert(new PdfToImgRequestData(
            pdf: $this->pdfFixtureContents(),
            pages: '1',
            maxPages: 1,
            dpi: 72,
            quality: 80,
            maxWidth: 1000,
            maxHeight: 1000,
            oversize: 'scale',
            strictPageValidation: true,
        ));

        $this->assertSame(1, $response->getPageCount());
        $this->assertSame(1, $response->getRenderedCount());
        $this->assertSame(1, $response->getPages()[0]->getPage());
        $this->assertSame('image/jpeg', $response->getPages()[0]->getContentType());
        $this->assertNotSame('', $response->getPages()[0]->getImage());
        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @throws PdfToImgException
     * @return void
     */
    public function testLocalPopplerIntegrationConvertsMultiPagePdfWhenBinariesAreAvailable(): void
    {
        $this->skipIfLocalPopplerUnavailable();
        Storage::fake('local');

        $response = (new LocalPopplerPdfToImgConverter('local', 'pdf-to-img/tmp'))->convert(new PdfToImgRequestData(
            pdf: $this->pdfFixtureContents('two-pages.pdf'),
            maxPages: 2,
            dpi: 72,
            quality: 80,
            maxWidth: 1000,
            maxHeight: 1000,
            oversize: 'scale',
            strictPageValidation: true,
        ));

        $this->assertSame(2, $response->getPageCount());
        $this->assertSame(2, $response->getRenderedCount());
        $this->assertSame([1, 2], array_map(fn (PdfToImgPageData $page) => $page->getPage(), $response->getPages()));
        $this->assertSame([], Storage::disk('local')->allFiles('pdf-to-img/tmp'));
    }

    /**
     * @param string $connection
     * @param array|null $config
     * @return void
     */
    protected function setPdfToImgConnection(string $connection, ?array $config = null): void
    {
        Config::set('forus.pdf_to_img.default', $connection);

        if ($config !== null) {
            Config::set("forus.pdf_to_img.connections.$connection", $config);
        }

        $this->app->forgetInstance(PdfToImgConverterContract::class);
        $this->app->forgetInstance(PdfToImgService::class);
        $this->app->forgetInstance('pdf_to_img');
    }

    /**
     * @return PdfToImgRequestData
     */
    protected function makeConverterRequest(): PdfToImgRequestData
    {
        return new PdfToImgRequestData(
            pdf: 'pdf-bytes',
            pages: '1',
            maxPages: 15,
            dpi: 150,
            quality: 85,
            maxWidth: 3000,
            maxHeight: 3000,
            oversize: 'scale',
            strictPageValidation: false,
        );
    }

    /**
     * @param PdfToImgRequestData $request
     * @param string $message
     * @return void
     */
    protected function assertRequestNormalizationFails(PdfToImgRequestData $request, string $message): void
    {
        try {
            $request->normalize();

            $this->fail('The request option should be rejected.');
        } catch (PdfToImgException $e) {
            $this->assertSame($message, $e->getMessage());
        }
    }

    /**
     * @param array $connection
     * @return callable|CredentialsInterface
     */
    protected function makeProviderCredentials(array $connection): callable|CredentialsInterface
    {
        return (new class ($this->app) extends PdfToImgServiceProvider {
            /**
             * @param array $connection
             * @return callable|CredentialsInterface
             */
            public function credentials(array $connection): callable|CredentialsInterface
            {
                return $this->makeCredentials($connection);
            }
        })->credentials($connection);
    }

    /**
     * @param string $key
     * @return array
     */
    protected function makeLambdaSuccessPayload(string $key): array
    {
        return [
            'pageCount' => 2,
            'renderedCount' => 1,
            'dpi' => 150,
            'quality' => 85,
            'pages' => [
                ['page' => 1, 'contentType' => 'image/jpeg', 'width' => 120, 'height' => 240, 'key' => $key],
            ],
            'warnings' => [],
        ];
    }

    /**
     * @param Result $result
     * @param bool $cleanup
     * @return array
     */
    protected function makeAwsConverter(Result $result, bool $cleanup = true): array
    {
        $mock = new MockHandler([$result]);

        return [
            new class (
                client: new LambdaClient([
                    'version' => 'latest',
                    'region' => 'eu-west-1',
                    'credentials' => [
                        'key' => 'test-key',
                        'secret' => 'test-secret',
                    ],
                    'handler' => $mock,
                ]),
                functionName: 'pdf-to-img-lambda-service',
                qualifier: 'prod',
                bucket: 'pdf-bucket',
                cleanup: $cleanup,
            ) extends AwsLambdaPdfToImgConverter {
                /**
                 * @return string
                 */
                protected function makeConversionId(): string
                {
                    return 'conversion-1';
                }
            },
            $mock,
        ];
    }

    /**
     * @return LocalPopplerPdfToImgConverter
     */
    protected function makeLocalPopplerConverterWithMissingPdftoppm(): LocalPopplerPdfToImgConverter
    {
        return new class (
            disk: 'local',
            path: 'pdf-to-img/tmp',
            pdftoppmBinary: 'missing-pdftoppm-binary',
            timeout: 1,
        ) extends LocalPopplerPdfToImgConverter {
            /**
             * @param string $pdfPath
             * @return int
             */
            protected function getPdfPageCount(string $pdfPath): int
            {
                return 1;
            }

            /**
             * @param string $pdfPath
             * @param int $page
             * @return array{widthPts: float, heightPts: float}
             */
            protected function getPdfPageSize(string $pdfPath, int $page): array
            {
                return [
                    'widthPts' => 595.0,
                    'heightPts' => 842.0,
                ];
            }
        };
    }

    /**
     * @param int $pageCount
     * @return LocalPopplerPdfToImgConverter
     */
    protected function makeLocalPopplerConverterThatRendersPage(int $pageCount = 1): LocalPopplerPdfToImgConverter
    {
        return new class ('local', 'pdf-to-img/tmp', $pageCount) extends LocalPopplerPdfToImgConverter {
            /**
             * @param string $disk
             * @param string $path
             * @param int $pageCount
             */
            public function __construct(string $disk, string $path, protected int $pageCount)
            {
                parent::__construct($disk, $path);
            }

            /**
             * @param string $pdfPath
             * @return int
             */
            protected function getPdfPageCount(string $pdfPath): int
            {
                return $this->pageCount;
            }

            /**
             * @param string $pdfPath
             * @param int $page
             * @return array{widthPts: float, heightPts: float}
             */
            protected function getPdfPageSize(string $pdfPath, int $page): array
            {
                return [
                    'widthPts' => 595.0,
                    'heightPts' => 842.0,
                ];
            }

            /**
             * @param string $pdfPath
             * @param string $runPath
             * @param int $page
             * @param int $dpi
             * @param int $quality
             * @return PdfToImgPageData
             */
            protected function renderPage(
                string $pdfPath,
                string $runPath,
                int $page,
                int $dpi,
                int $quality,
            ): PdfToImgPageData {
                Storage::disk('local')->put("$runPath/page-0001.jpg", 'jpeg-bytes');

                return new PdfToImgPageData(
                    page: 1,
                    contentType: 'image/jpeg',
                    width: 10,
                    height: 20,
                    image: 'jpeg-bytes',
                );
            }
        };
    }

    /**
     * @return LocalPopplerPdfToImgConverter
     */
    protected function makeLocalPopplerConverterThatFailsDuringRender(): LocalPopplerPdfToImgConverter
    {
        return new class ('local', 'pdf-to-img/tmp') extends LocalPopplerPdfToImgConverter {
            /**
             * @param string $pdfPath
             * @return int
             */
            protected function getPdfPageCount(string $pdfPath): int
            {
                return 1;
            }

            /**
             * @param string $pdfPath
             * @param int $page
             * @return array{widthPts: float, heightPts: float}
             */
            protected function getPdfPageSize(string $pdfPath, int $page): array
            {
                return [
                    'widthPts' => 595.0,
                    'heightPts' => 842.0,
                ];
            }

            /**
             * @param string $pdfPath
             * @param string $runPath
             * @param int $page
             * @param int $dpi
             * @param int $quality
             * @throws PdfToImgException
             * @return PdfToImgPageData
             */
            protected function renderPage(
                string $pdfPath,
                string $runPath,
                int $page,
                int $dpi,
                int $quality,
            ): PdfToImgPageData {
                Storage::disk('local')->put("$runPath/page-0001.jpg", 'partial-jpeg-bytes');

                throw new PdfToImgException('Render failed.');
            }
        };
    }

    /**
     * @return void
     */
    protected function configureS3PdfToImgDisk(): void
    {
        Config::set('filesystems.disks.s3_pdf_to_img', [
            'driver' => 's3',
            'key' => 'test-key',
            'secret' => 'test-secret',
            'region' => 'eu-west-1',
            'bucket' => 'pdf-bucket',
            'throw' => true,
        ]);
    }
}
