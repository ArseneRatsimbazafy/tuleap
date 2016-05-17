module.exports = function (grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        sass: {
            dist: {
                options: {
                    sourcemap: 'none',
                    style: 'compressed',
                    loadPath: ['/tuleap/plugins/trafficlights/www/themes/FlamingParrot/css']
                },
                files: {
                    "www/themes/default/css/style.css": "www/themes/default/css/style.scss",
                    "www/themes/FlamingParrot/css/style.css": "www/themes/FlamingParrot/css/style.scss"
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-contrib-sass');

    grunt.registerTask('default', ['sass']);
};
