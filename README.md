# README

This is a read only method of viewing a repository full of md documents, powered by [parsedown](https://github.com/erusev/parsedown). You will still need to edit the files in a text or markdown editor, but simply install this on a php server and point it to your directory and it will glob all the files and convert all markdown to html. All conversion is handled live, so you merely need to save and refresh your browser to view updates.


### Features
* Live conversion of md to html
* built in search
* works with an existing md repository

### Use cases
* want to easily document procedures for yourself/others
* and more!

### Installation
1. clone repo into a webserver with php that also has access to the markdown files (this can be a mounted fileshare)
	1. `git clone https://github.com/mredig/markdownrepo`
	1. `git submodule init`
	1. `git submodule update`
1. copy `config-sample.php` to `config.php` and customize the settings within
1. navigate to the webserver address where you cloned into
	* `http://serveraddress/markdownrepo`

### Requirements
* server running php
* browser
* markdown files
	* file extension must be lowercase
