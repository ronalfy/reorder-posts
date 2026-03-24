module.exports = function (grunt) {
	grunt.initConfig({
	  compress: {
		main: {
		  options: {
			archive: 'reorder-posts.zip'
		  },
		  files: [
			{ src: ['reorder-posts.php'], dest: '/', filter: 'isFile' },
			{ src: ['css/**'], dest: '/' },
			{ src: ['scripts/**'], dest: '/' },
			{ src: ['languages/**'], dest: '/' },
			{ src: ['class-reorder-admin.php'], dest: '/', filter: 'isFile' },
			{ src: ['class-reorder.php'], dest: '/', filter: 'isFile' },
			{ src: ['index.php'], dest: '/', filter: 'isFile' },
			{ src: ['readme.txt'], dest: '/', filter: 'isFile' },
			{ src: ['uninstall.php'], dest: '/', filter: 'isFile' },
		  ]
		}
	  }
	})
	grunt.registerTask('default', ['compress'])
  
	grunt.loadNpmTasks('grunt-contrib-compress')
  }
  