"use strict";

module.exports = function (grunt) {

    grunt.initConfig({
        watch: {
            // Watch for any changes to less files and compile.
            files: ["scss/*.scss"],
            tasks: ["compile"],
            options: {
                spawn: false,
                livereload: true
            }
        },
        sass: {
            options: {
                style: 'expanded'
            },
            dist: {
                files: {
                    "styles.css": "scss/plugins.scss"
                }
            }
        },
    });

    // Load contrib tasks.
    grunt.loadNpmTasks("grunt-contrib-watch");

    // Load core tasks.
    grunt.loadNpmTasks("grunt-sass");

    // Register tasks.
    grunt.registerTask("default", ["watch"]);
    grunt.registerTask("compile", ["sass"]);
};
