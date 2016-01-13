# PDFStream

[![Build Status](https://travis-ci.org/teisvkn/PDFStream.svg)](https://travis-ci.org/teisvkn/PDFStream)

Extracts stream-blocks from a PDF file into individual files, with their md5 sum as file name, and replaces the stream-block with its md5 sum as a reference, in the PDF.
And of course it is able to restore the PDF.

## Use case

You generate and store a lot of PDF files from the same template, containing images and maybe embedded fonts.
This results in quite big PDF files, taking into account that some of their content is redundant.
By extracting the stream-blocks from the PDF files, and storing them with their md5 sum as file name, each unique stream-block will only have to be stored once.

## Todo

GZIP the remaining PDF content after extracting the stream-blocks.