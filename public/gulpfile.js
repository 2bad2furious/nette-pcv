var gulp = require('gulp'),
    nittro = require('gulp-nittro'),
    fs = require('fs');

gulp.task("default", defaultAction)

function defaultAction() {
    var options = require('./nittro.json');
    var history = fs.readFileSync('./HistoryHelper.js');

    var builder = new nittro.Builder(options);

    var builderSetUp = "var builder = new Nittro.DI.ContainerBuilder({";

    /** @var String */
    var fileContent = builder.buildJs();

    var finalFileContent = fileContent
        .replace(history, "")
        .replace(builderSetUp, history + "\n" + builderSetUp);

    fs.writeFileSync("./nittro.js", finalFileContent);
}