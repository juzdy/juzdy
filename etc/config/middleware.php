<?php
return [
    'middleware' => [
        
        'global' => [
            /**
            * Global middleware that runs for all requests
            */

            \Juzdy\Http\Middleware\CorsMiddleware::class,

            /**
             * Router middleware should be the last in the global stack
             */
            \Juzdy\Http\Router::class
        ],
        
        // Middleware groups for different areas
        'groups' => [
            /**
             * Group middlewares based on handler interfaces they implement.
             *  
             * Example: 
             * \Acme\Foo\AuthintecableInterface::class => [
             *  \Bar\Foo\BasicAuthMiddleware::class,
             * ],
             * \Acme\Foo\LoggableInterface::class => [
             *  \Bar\Foo\RequestLoggingMiddleware::class,
             * ],
             */
        ],
    ],
];