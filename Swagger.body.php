<?php

/** vim: set ts=2 sw=2 et ai: */

/** Copyright (C) 2020 Pablo Gra\~na
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
use MediaWiki\Logger\LoggerFactory;

/** The swagger render extension main class.
 */
class Swagger {

  /** Register this extension with the WikiText parser.
   *
   * This sets up the parser to parse the <swagger> ... </swagger>.
   */
  public static function onParserFirstCallInit(&$parser) {
    $swagger = new self();
    $parser->setHook('swagger', [$swagger, 'renderSwagger']);
    return true;
  }

  /** Clean the generated swagger html files when saving the page.
   */
  public static function onPageContentSave(&$wikiPage, &$user, &$content,
    &$summary, $isMinor, $isWatch, $section, &$flags, &$status) {

    $swagger = new self();
    $swagger->cleanGeneratedFiles();

    return true;
  }

  /** Constructor.
   */
  function __construct() {
    $this->error = null;
    $this->logger = LoggerFactory::getInstance('Swagger');

    $this->debug = $this->getProperty('Debug');
    if ($this->debug) {
      $this->logger->debug("Swagger constructed");
    }
  }

  /** Return an error Message.
   *
   * @param $context - the context in which the error happened
   *
   * @return - the error message
   */
  function getErrorMsg($context) {
    $errMsg="An error occured in the Swagger extension.";
    if (isset($this->error)) {
      $errMsg .= "<br>" . $this->error;
    }
    return $errMsg;
  }

  /** Support debugging by either storing or in debug mode logging a debug
   * message
   *
   * @param msg - the message to store or log 
   */
  function debug($msg) {
    $this->msg = $msg;
    if ($this->debug) {
      $this->logger->debug($msg);
    }
  }

  /** Renders the html content of one <swagger> ... </swagger> element.
   *
   * @return an html string with an <object> tag that references the generated
   * html.
   */
  function renderSwagger($input, $args, Parser $parser, PPFrame $frame) {
    $content = $this->getGeneratedHtml($input, $args, $parser);

    // if there is no content
    if ($content['src'] == false) {
      $text = $this->getErrorMsg($content);
    } else {
      $text = '';
      $text .= '<object style="width:100%;" type="text/html" ';
      $text .= 'onload="this.height=this.contentWindow.document.body.scrollHeight+100;" ';
      $text .= 'data="'.$content['src'].'"> </object>';
    }
    return $text;
  }

  /** Obtains the swagger html.
   *
   * Looks for a previously generated swagger html from the cache. If it is not
   * found, it uses swagger-codegen to generate the html from the yaml source,
   * and store it in the cache.
   *
   * This operation creates file names of the form swagger-md5(title)-md5(src).
   *
   * @param string model in been format
   *
   * @returns an array with the following elements:
   *
   *   'title': the page title.
   *
   *   'src': the webserver based URL to the generated swagger spec. If
   *   anything fails, this value is false.
   *
   *   'file': the file system path to the generated swagger spec.
   */
  function getGeneratedHtml($swaggerSrc, $argv, $parser = null) {
    // Compute the page title and swagger md5 hashes. These are the base for
    // the generated file name.
    $title = $this->getPageTitle($parser);
    $title_hash = md5($title);
    $swagger_hash = md5($swaggerSrc);

    $filename_prefix = 'swagger-' . $title_hash . "-" . $swagger_hash;
    $dirname = $this->getUploadDirectory();
    $full_path_prefix = $dirname . "/" . $filename_prefix;
    $generatedFileName = $dirname . "/" . $filename_prefix . ".html";

    // Initialize the result.
    $result = array(
      'title' => $title, 'src' => false, 'file' => ''
    );

    // Check cache. When found, reuse it. When not, generate a new html.
    if (is_file($generatedFileName)) {
      $result['file'] = $generatedFileName;
    } else {
      $result['file'] = $this->renderSwaggerLocal($swaggerSrc,
        $generatedFileName, $dirname, $filename_prefix);
    }
    $result['src'] = $this->getUploadPath() . "/" . basename($result['file']);
    return $result;
  }

  /** Returns the directory to generate the html swagger files.
   *
   * This is defaults to the mediawiki images directory. It must be writable.
   */
  function getUploadDirectory() {
    global $wgUploadDirectory;
    return $wgUploadDirectory;
  }

  /** Returns the url of the generated html swagger files.
   *
   * This is defaults to the mediawiki images url.
   */
  function getUploadPath() {
    global $wgUploadPath;
    return $wgUploadPath;
  }

  /** Clean the generated files for the current page.
   */
  function cleanGeneratedFiles() {
    $title = $this->getPageTitle($parser);
    $title_hash = md5($title);
    $path = $this->getUploadDirectory() . "/swagger-" . $title_hash . "-*.html";
    $files = glob($path);
    foreach ($files as $filename) {
      unlink($filename);
    }
  }

  /**
  * Renders a Swagger model by the using the following method:
  *  - Use a filename a md5 hash of the uml source
  *  - Launch Swagger to create the PNG file into the picture cache directory
  *
  * @param string swaggerSrc
  *
  * @param string generatedFileName: full path of to-be-generated image file.
  *
  * @param string dirname: directory of generated files
  *
  * @param string filename_prefix: unique prefix for $dirname
  *
  * @returns the full path location of the rendered picture when
  *     successfull, false otherwise
  */
  function renderSwaggerLocal($swaggerSrc, $generatedFileName, $dirname,
      $filename_prefix) {

    // create temporary swagger text file encoded in utf-8:
    $swaggerFile = $dirname . "/".$filename_prefix . ".swagger";
    $fp = fopen($swaggerFile, "wb");
    $w  = fputs($fp, mb_convert_encoding($swaggerSrc, 'UTF-8'));
    fclose($fp);

    // Create a temp directory where swagger will create the index.html:
    $tmpDir = $dirname . "/" . $filename_prefix . "-tmp";
    mkdir($tmpDir);

    // Lauch Swagger
    $jarFile = $this->getProperty('JarFile');
    if (!is_file($jarFile))
      $jarFile = dirname(__FILE__) . '/' . $jarFile;
    if (!is_file($jarFile)) {
      $this->debug("$jarfile is missing");
      $this->error = $this->msg;
    }

    // java -jar swagger-codegen-cli.jar generate -i api.yaml -l html2
    // We remove the default html generated information.
    $command = "java -jar " . $jarFile . " generate "
      . "--additional-properties infoEmail=,infoUrl=,licenseInfo=,licenseUrl= "
      . "-l html -o \"{$tmpDir}\" -i \"{$swaggerFile}\"";

    // execute the java command
    exec($command,$output,$status_code);
    $this->debug("command '" . $command . "' returned status code="
      . $status_code . "");
    $this->debug(implode("<br>", $output));
    $this->error = $this->msg;

    $this->debug("image file '" . $generatedFileName . "'");

    rename ($tmpDir . "/index.html", $generatedFileName);

    // Clean up the temporary files.
    unlink($swaggerFile);
    unlink($tmpDir . '/.swagger-codegen-ignore');
    unlink($tmpDir . '/.swagger-codegen/VERSION');
    rmdir($tmpDir . '/.swagger-codegen');
    rmdir($tmpDir);

    // Only return existing path names.
    if (is_file($generatedFileName)) {
      return $generatedFileName;
    } else {
      $this->debug("Swagger generated file is missing");
    }

    return false;
  }

  /** Get the title of the current page.
   *
   * @return a string with the title.
   */
  function getPageTitle($parser = null) {

    global $wgTitle;

    if ($parser != null) {
      $title = $parser->getTitle()->getFulltext();
    }
    if ($title == null) {
      $title = $wgTitle;
    }

    $this->debug("Page title: '" . $title . "'");
    return $title;
  }

  /** Return a property for swagger using global, request or passed default.
   */
  function getProperty($name) {
    global $wgRequest;
    $prefix = "Swagger";
    if ($wgRequest->getText($prefix.$name)) {
      return $wgRequest->getText($prefix.$name);
    }
    if ($wgRequest->getText("amp;" . $prefix.$name)) {
      // hack to handle ampersand entities in URL
      return $wgRequest->getText("amp;" . $prefix.$name);
    }
    $config = ConfigFactory::getDefaultInstance()->makeConfig('Swagger');
    return $config->get($prefix.$name);
  }
}

