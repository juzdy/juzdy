<?php
namespace App\Http\Handler;

use Juzdy\Http\Handler;
use Juzdy\Http\RequestInterface;
use Juzdy\Http\ResponseInterface;

/**
 * Demo Index Handler
 */
class Index extends Handler
{

    public function __construct(
        // Inject dependencies here if needed
    )
    {}

    /**
     * {@inheritdoc}
     */
    public function handle(RequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->header('Content-Type', 'text/plain')
            ->body('Hello from Index Handler!');
            ;
    }
}