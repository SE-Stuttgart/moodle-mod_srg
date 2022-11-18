# Student Report Generation (SRG) #

## German description

**English version please see below**

Das Plugin wurde entwickelt, um die Evaluation eines Kurses zu unterstützen. Es bietet Arbeitsmaterialien vom Typ "student report generation", die es den Lernenden ermöglichen, einen Teil ihrer Moodle-Logdaten in anonymisierter Form herunterzuladen.


Wenn die Lernenden auf diese Art von Arbeitsmaterial klicken, wird ihnen eine konfigurierbare Beschreibung angezeigt. Außerdem gibt es zwei Schaltflächen: zum einen eine Schaltfläche, um die anonymisierten Protokolldaten, die zum Download bereitstehen, anzusehen. Eine zweite Schaltfläche ermöglicht es, diese Daten als Datei herunterzuladen. Die Protokolldaten enthalten keine Personennamen, und auch die Benutzer-ID wurde entfernt. Daher ist es nicht möglich, aus den Daten Rückschlüsse auf die jeweilige Person zu ziehen. Die heruntergeladene Protokolldatei hat die Endung .kib3. Wenn Sie also den Inhalt einer solchen .kib3-Datei überprüfen wollen, ändern Sie einfach die Endung in .zip und entpacken Sie sie.


 
## English description

The plugin is developed to support the evaluation of a course. It provides "log data creation" type work material that allows learners to download a portion of their Moodle log data in anonymized form.


When learners click on this type of work material, they are shown a configurable description. There are also two buttons: on the one hand, a button to view the anonymized log data available for download. A second button allows to download this data as a file. The log data does not contain any names of persons, and the user ID has also been removed. Therefore, it is not possible to draw any conclusions about the respective person from the data. The downloaded log file has the extension .kib3. Therefore, if you want to check the contents of such a .kib3 file, simply change the extension to .zip and unzip it.


### Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/srg

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.



## License ##

2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
