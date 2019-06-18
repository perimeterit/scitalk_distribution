JW Player module for Drupal.

SUMMARY
-----------------------------------------------
The JW Player module adds a new field for displaying video files in a JW Player.

For a full description visit the project page:
  http://drupal.org/project/jw_player
Bug reports, feature suggestions and latest developments:
  http://drupal.org/project/issues/jw_player


REQUIREMENTS
-----------------------------------------------
* This module depends on the File module, which is part of Drupal core, Chaos
  Tools (http://drupal.org/project/ctools) and the Libraries module
  (http://drupal.org/project/libraries).


INSTALLATION
-----------------------------------------------
* Download either the latest commercial or the latest non-commercial JW
  Player at http://www.longtailvideo.com/players/jw-flv-player/.

* Extract the zip file and put the contents of the extracted folder in
  libraries/jwplayer. 
  E.g.: sites/all/libraries/jwplayer or sites/<sitename>/libraries/jwplayer
	
* Install this module as described at http://drupal.org/node/895232.

* Go to Administration > Reports > Status reports (admin/reports/status) to
  check your configuration.


BASIC USAGE
-----------------------------------------------
In that majority of cases JW Player is used as a field formatter on a file
field. Before enabling JW Player on a field visit /admin/config/media/jw_player
to configure one or more presets. A preset is a group of JW Player settings,
such as dimensions and skin, that can be re-used multiple times.

Once a preset has been defined visit /admin/structure/types and select "manage
display" for the content type you'd like to configure and select "JW player" as
the formatter on the relevant file field. At this point you will also need to
click on the cog beside the field to select the preset you'd like to apply to
the file. That's it - videos uploaded to this field should now be displayed
using JW Player!

CUSTOM SKINS
-----------------------------------------------
Custom skins should be placed in a folder called "jwplayer_skins" in your
libraries directory (for example, sites/all/libraries). For versions older than
JW Player 7, the skin file will have an extension of ".xml" or ".swf". For
JW Player 7 and newer, all skins are defined as CSS files, and should have an
extension of ".css".

For additional information on creating custom skins, see the "Player Styling"
section below. You can also see examples of JW Player 7 skins by downloading
a self-hosted copy of JW Player, and reviewing the files located in the "skins"
subdirectory in the jwplayer folder.

URL BASED SEEKING
-----------------------------------------------
You can create permanent links that make jWPlayer start playing at a given
time frame. The url must look like this:

 /path/to/site?seek=<TIME>#<PLAYER_ID>

<TIME> is the offset in seconds the player should start and <PLAYER_ID>
is the id of the player the seeking is targeted on. This enables seeking
for sites with multiple instances of jWPlayer on it.

Not that seeking only works if the server delivering the media file is
capable of doing so. If the Server does not support this the player will
always start at the beginning.

LIMITATIONS
-----------------------------------------------
1) It is not possible to have multiple JW Player cloud-based presets on the
   same page. In such a situation, each player will have its own Player Library
   URL. When loaded on the same page, there will be a conflict. The way to
   resolve this issue is use one Cloud Player Library URL (the default defined
   on the General Settings page at /admin/config/media/jw_player/settings), and
   set each preset used on the page to have Drupal-defined settings.

2) Autoplay, loop, and muted functionality does not work on all browsers. Please
   refer to this page for information on which browsers support the attributes:
   https://www.jwplayer.com/html5/autoloop/