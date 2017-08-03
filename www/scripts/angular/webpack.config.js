/* eslint-disable */
var path                  = require('path');
var webpack               = require('webpack');

var assets_dir_path = path.resolve(__dirname, './bin/assets');
module.exports = {
    entry : {
        trafficlights: './src/app/app.js',
    },
    output: {
        path    : assets_dir_path,
        filename: '[name].js'
    },
    resolve: {
        modules: [
            'node_modules',
            'vendor'
        ],
        alias: {
            // We should probably package angular-ui-bootstrap-templates for npm ourselves
            'angular-ui-bootstrap-templates'  : 'angular-ui-bootstrap-bower/ui-bootstrap-tpls.js',
            // Bower only deps
            'angular-ui-utils'                : 'angular-ui-utils/unique.js',
            'angular-filter-pack'             : 'angular-filter-pack/dist/angular-filter-pack.js',
            // Modal deps should be required by modal
            'angular-ckeditor'                : 'angular-ckeditor/angular-ckeditor.js',
            'angular-bootstrap-datetimepicker': 'angular-bootstrap-datetimepicker/src/js/datetimepicker.js',
            'angular-ui-select'               : 'angular-ui-select/dist/select.js',
            'angular-filter'                  : 'angular-filter/index.js',
            'angular-base64-upload'           : 'angular-base64-upload/index.js',
            'tuleap-artifact-modal'           : 'artifact-modal/dist/tuleap-artifact-modal.js',
        }
    },
    externals: {
      tlp: 'tlp'
    },
    module: {
        rules: [
            {
                test: /\.html$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'ng-cache-loader',
                        query: '-url'
                    }
                ]
            },
            {
                test: /\.po$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'angular-gettext-loader',
                        query: 'browserify=true'
                    }
                ]
            }
        ]
    },
    plugins: [
        // This ensure we only load moment's fr locale. Otherwise, every single locale is included !
        new webpack.ContextReplacementPlugin(/moment[\/\\]locale$/, /fr/)
    ]
};
