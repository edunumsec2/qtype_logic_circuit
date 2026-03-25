# Logic Circuit Question Type

## Requirements

* Moodle v4.0 or later
* PHP v8.1 or later

## Description

Moodle question type plugin that serves as a wrapper around the [logic circuit editor](https://github.com/jppellet/Logic-Circuit-Simulator). This plugin allows the creation of the logic cicruit type questions in Moodle quizzes.

## Main features

This plugin allows for the creation of logic circuit questions in Moodle quizzes. The main features are :

* Definition of the initial state of the logic circuit using the logic circuit editor.
* Automatic grading of the questions based on the predefined test cases.
* The teacher can define the mode in which the logic circuit ecitor is presented:
    * **Complete** - the students can add/remove and reorganise the avaialbe circuit components
    * **Connection-only** - the students can only connect the already-present components
* The teacher can also define which components are displayed to the students for more precise questions.
* The plugin is translated into English and French.

## Installation

**Either**

1. Get the files from this repository.
2. Place them into `public/question/type/logiccircuit` folder.
3. Start the Moodle instance.

**or**

1. Log into the already running Moodle instance as administrator.
2. Navigate to `Site administration -> Plugins -> Install plugins`
3. Drop the contents of thie repo in a zip file and click `Install plugin from the ZIP file`

## Testing

The unit tests can be executed using the `phpunit` command inside a Moodle project.



