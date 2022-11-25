#!/bin/bash

JAR=swagger-codegen-cli.jar


JAR=swagger-codegen-cli-3.0.36.jar

java -jar $JAR generate \
  --additional-properties "infoEmail=,infoUrl=,licenseInfo=,licenseUrl=" \
  -l html -o target/html-old -i tests/sample.yaml

java -jar $JAR generate \
  --additional-properties "infoEmail=,infoUrl=,licenseInfo=,licenseUrl=" \
  -l html2 -o target/html2-old -i tests/sample.yaml

JAR=swagger-codegen-cli.jar

java -jar $JAR generate \
  --additional-properties "infoEmail=,infoUrl=,licenseInfo=,licenseUrl=" \
  -l html -o target/html-new -i tests/sample.yaml

java -jar $JAR generate \
  --additional-properties "infoEmail=,infoUrl=,licenseInfo=,licenseUrl=" \
  -l html2 -o target/html2-new -i tests/sample.yaml

