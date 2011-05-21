=============================
Blockdiag MediaWiki Extension
=============================

requirement
===========

- blockdiag_
- mediawiki >1.16

.. _blockdiag: http://tk0miya.bitbucket.org/blockdiag/build/html/

install
=======

1. Copy blockdiag.php to ${MEDIAWIKI_ROOT}/extension/ ::

   $ sudo cp blockdiag.php ${MEDIAWIKI_ROOT}/extension/

2. Edit LocalSettings.php 

::

   $ sudo vim ${MEDIAWIKI_ROOT}/LocalSettings.php
   ## Add follow lines
   require_once("$IP/extensions/blockdiag.php");
   $wgVerifyMimeType = false;
   $wgAllowTitlesInSVG=true;

known issues
============

- Need reload page after add or edit blockdiag chart.

