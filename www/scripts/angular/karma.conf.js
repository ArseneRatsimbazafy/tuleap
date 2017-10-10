var webpack_config = require('./webpack.config.js');

webpack_config.module.rules.push({
    test: /\.js$/,
    exclude: [
        /node_modules/,
        /vendor/
    ],
    use: [
        {
            loader: 'istanbul-instrumenter-loader',
            query : 'esModules=true'
        }
    ]
});

// Karma configuration
module.exports = function(config) {
    config.set({

        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: '.',

        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['jasmine'],

        // list of files / patterns to load in the browser
        // This is filled by gulp
        files: [
            'node_modules/jasmine-promise-matchers/dist/jasmine-promise-matchers.js',
            '../../../../../src/www/scripts/d3/v4/d3.min.js',
            'src/app/tlp-mock.spec.js',
            'src/app/app.spec.js'
        ],

        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            'src/app/app.spec.js': ['webpack']
        },

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // start these browsers
        // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
        browsers: [process.platform !== 'linux' ? 'ChromeHeadless' : 'ChromiumHeadless'],

        webpack: webpack_config,

        webpackMiddleware: {
            stats: 'errors-only'
        }
    });
};
