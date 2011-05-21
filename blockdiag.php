<?php
/**
 * @filename: blockdiag.php
 * @license: LGPL (GNU Lesser General Public License) http://www.gnu.org/licenses/lgpl.html
 * @author: Kazunori Kojima
 */

/**
  blockdiag_example:
  {
    A -> B -> C
         B -> D    
  }
**/

if( defined( 'MEDIAWIKI' ) ) {
	$wgHooks['ParserFirstCallInit'][] = 'blockdiagMain';
	$wgJobClasses['uploadBlockdiag'] = 'UploadBlockdiagJob';
	$wgExtensionCredits['parserhook'][] = array(
		'name'		=> 'blockdiag',
		'author'	=> 'Kazunori Kojima',
		'url'		=> '',
		'description'	=> 'Allow the use of blockdiag syntax.'
	);

	function blockdiagMain( &$parser ){
		$parser->setHook( 'blockdiag', 'blockdiagDisplay' );
		return true;
	}


	function blockdiagDisplay( $input, $args, $parser ){
		global $wpTmpDirectory;
		$newBlockdiag = new Blockdiag($parser, $wpTmpDirectory, $input);
		$html = $newBlockdiag->showImage();

		return $html;
	}
}

/**
 * Blockdiag
 **/
class Blockdiag {
	private $_blockdiag_path = '/usr/bin/blockdiag';
	private $_dstFileName = '';
	private $_srcFileName;
	private $_uploadComment;
	private $_parser;
	private $_title;
	private $_imgType = 'svg';
	private $_tmpDir;

	public function __construct( $parser,  $tmpDir, $source )
	{
        	if(!is_file($this->_blockdiag_path))
        	{
        	    throw new Exception('blockdiag is not found at the specified place ($_blockdiag_path).', 1);
        	    return FALSE;
		}
		$this->_parser = $parser;
		$this->_title = $parser->getTitle();
		$this->_tmpDir = $tmpDir;
		$this->_dstFileName = md5($source) . '.' . $this->_imgType;
		$this->_srcFileName = $this->_write_src($source);
	}
	
	public function showImage() {
		$file = wfFindFile($this->_dstFileName);	
		if( $file && $file->isVisible() ){
			$filename = $file->getTitle();
			return $this->_getImage( $filename );
		} else {
			$this->_uploadComment = 'generate by '.$this->_title->getFullText();
			$this->_generate();
			return $this->_getImage( $this->_dstFileName );
		}
	}
	private function _getImage( $filename ) {
		return $this->_parser->recursiveTagParse("[[" . $filename . "]]" );
	}

	private function _write_src( $input ) {
		$tmpName = tempnam( $this->_tmpDir, 'blockdiag' );
		$fp = fopen( $tmpName, 'w');
		fwrite($fp, $input);
		fclose($fp);

		return $tmpName;
	}

	private function _generate(){
		$out_tmpName = tempnam( $this->_tmpDir, 'blockdiag' );
		
		// generate blockdiag image
		exec( $this->_blockdiag_path . " -T " . $this->_imgType . " -o " . $out_tmpName. " " . $this->_srcFileName );

		// upload
		$jobParams = array( 
			'tmpName' => $out_tmpName,
			'dstName' => $this->_dstFileName,
			'comment' => $this->_uploadComment,
			'size'    => filesize($out_tmpName),
		);
		$job = new UploadBlockdiagJob( $this->_title, $jobParams );
		if( $job->insert() ) {
			return true;
		}	
	}
}

/* 
 * UploadBlockdiagJob
 *   
 *  based on QrCode.php
 *
 */
// not changeable 
define('BLOCKDIAG_BOT','blockdiag generator');	// if a user changes this, the name won't be protected anymore
$wgReservedUsernames[] = BLOCKDIAG_BOT;	// Unless we removed the var from his influence

class UploadBlockdiagJob extends Job {
	public function __construct( $title, $params, $id = 0 ) {
		$this->_dstFileName = $params['dstName'];
		$this->_tmpName = $params['tmpName'];
		$this->_uploadComment = $params['comment'];
		$this->_fileSize = $params['size'];
		$this->title = $title;
		parent::__construct( 'uploadBlockdiag', $title, $params, $id );
	}

	/**
	 * Handle the mediawiki file upload process
	 * @return boolean status of file "upload"
	 */
	public function run() {
		global $wgOut;

		$mUpload = new UploadFromFile();
		$mUpload->initialize( $this->_dstFileName, $this->_tmpName, $this->_fileSize );

		$pageText = 'blockdiag '.$this->_dstFileName.', generated on '.date( "r" )
                        .' by the blockdiag Extension for page [['.$this->title->getFullText().']].';


		// Upload verification
		$details = $mUpload->verifyUpload();
		if ( $details['status'] != UploadBase::OK ) {
			var_dump($details);
			return false;
		}

		$status = $mUpload->performUpload( $this->_uploadComment, $pageText, false, $this->_getBot() );

		if ( $status->isGood() ) {
			return true;
		} else {
			$wgOut->addWikiText( $status->getWikiText() );
			return false;
		}
	}

	/**
	 * Create or select a bot user to attribute the code generation to
	 * @return user object
	 * @note there doesn't seem to be a decent method for checking if a user already exists
	 * */
	private function _getBot(){
		$bot = User::createNew( BLOCKDIAG_BOT );
		if( $bot != null ){
			wfDebug( 'blockdiag::_getBot: Created new user '. BLOCKDIAG_BOT ."\n" );
			//$bot->setPassword( '' );   // doc says empty password disables, but this triggers an exception
		} else {
			$bot = User::newFromName( BLOCKDIAG_BOT );
		}   

		if( !$bot->isAllowed( 'bot' ) ) {	// User::isBot() has been deprecated
			$bot->addGroup( 'bot' );
			wfDebug( 'blockdiag::_getBot: Added user '. BLOCKDIAG_BOT.' to the Bot group'."\n" );
		}

		return $bot;
	}
}
?>
