Welcome to the [Swagger Codegen](https://swagger.io/tools/swagger-codegen/)
plugin for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki).

# Prerequisites

1. A [Java](https://www.java.com/en/) installation. Can also be [OpenJDK](http://openjdk.java.net/).

# Installation

1. Go to the extensions folder of your MediaWiki installation. On RedHat and
   derivates this will be /usr/share/mediawiki/extensions.

   `cd /usr/share/mediawiki/extensions`

1. Create a new subdirectory Swagger in this folder and move to this
   directory.

   `mkdir Swagger && cd Swagger`
   
1. Move the all downloaded files into the Swagger directory.

   `mv <downloaddir>/*`

1. Get the swagger-codegen-cli-3.0.36.jar from maven central

   `wget https://repo1.maven.org/maven2/io/swagger/codegen/v3/swagger-codegen-cli/3.0.36/swagger-codegen-cli-3.0.36.jar`
   
1. Copy it to the Swagger extension directory:

   `mv swagger-codegen-cli-3.0.36.jar swagger-codegen-cli.jar`

1. (Optional) Adapt the getUploadPath and getUploadDirectory to your
   preference if you want these different from MediaWiki's standard settings.
   Mind that these directories must be writeable by the system user who runs
   MediaWiki.

1. Put the following line to your LocalSettings.php in
   MediaWiki's root folder to include the extension:
   
   `wfLoadExtension( 'Swagger' );`

1. Reload http

   `service httpd graceful`

1. Enjoy!

# Issues
If you have suggestions or remarks, please [file an issue](https://github.com/pablograna/swagger-mediawiki/issues)!

There is a pending issue in swagger-codegen-generators
(https://github.com/swagger-api/swagger-codegen-generators/issues/1087), to be
fixed in https://github.com/swagger-api/swagger-codegen-generators/pull/1088.
For the time being, you can get a build that includes the fix from this
repository (swagger-codegen-cli.jar).

