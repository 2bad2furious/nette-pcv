var gulp = require('gulp'),
    nittro = require('gulp-nittro');

var options = require('./nittro.json');
console.info(options)
var builder = new nittro.Builder(options);
