module.exports = function (grunt) {

  // Project configuration.
  grunt.initConfig({

    pkg: grunt.file.readJSON('package.json'),

    watch: {
      css: {
        files: ['**/*.scss'],
        tasks: ['sass'],
        options: {
          livereload: true,
          spawn: false
        }
      },
      twig: {
        files: ['**/*.twig'],
        options: {
          livereload: true,
        }
      }
    },

    sass: {
      options: {
        sourceMap: true,
        outputStyle: 'compressed',
        sourceComments: false
      },
      dist: {
        files: [{
          expand: true,
          cwd: './scss',
          src: ['*.scss'],
          dest: './css',
          ext: '.css'
        }],
      },
    },

    postcss: {
      options: {
        map: {
          inline: false,
          annotation: 'css',
          sourcesContent: true
        },
        processors: [
          require('autoprefixer')({browsers: 'last 2 versions'}),
          require('cssnano')()
        ]
      },
      dist: {
        src: './css/*.css'
      }
    }

  });

  // Load plugins
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-sass');
  grunt.loadNpmTasks('grunt-postcss');

  grunt.registerTask('default', ['sass', 'postcss']);
};
