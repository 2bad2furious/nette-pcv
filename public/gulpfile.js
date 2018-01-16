var gulp = require('gulp'),
    nittro = require('gulp-nittro'),
    fs = require('fs');

gulp.task("default",defaultAction)

function defaultAction(){
    var options = require('./nittro.json');
    console.info(options)
    var builder = new nittro.Builder(options);

    fs.writeFileSync("./nittro.js",builder.buildJs());
}