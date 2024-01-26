var glob = require('glob');
var fs = require('fs');

// For entry file selection
glob("fluent-pdf.php", function(err, files) {
        files.forEach(function(item, index, array) {
        var data = fs.readFileSync(item, 'utf8');
        var mapObj = {
            FLUENT_PDF_DEVELOPMENT : "FLUENT_PDF_PRODUCTION"
        };
        var result = data.replace(/FLUENT_PDF_DEVELOPMENT/gi, function(matched){
            return mapObj[matched];
        });
        fs.writeFile(item, result, 'utf8', function (err) {
            if (err) return console.log(err);
        });
        console.log('✅  Development asset enqueued!');
    });
});