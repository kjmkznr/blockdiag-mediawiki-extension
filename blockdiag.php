<?php
/**
 * @filename: blockdiag.php
 * @license: LGPL (GNU Lesser General Public License) http://www.gnu.org/licenses/lgpl.html
 * @author: Kazunori Kojima
 */

/**
  blockdiag_example:
  blockdiag {
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
		global $wgTmpDirectory;
		global $wgUploadDirectory;
		global $wgUploadPath;

		$wgBlockdiagDirectory = "$wgUploadDirectory/blockdiag";
		$wgBlockdiagUrl = "$wgUploadPath/blockdiag";

		$newBlockdiag = new Blockdiag( 
			$wgBlockdiagDirectory,
			$wgBlockdiagUrl,
			$wgTmpDirectory, 
			$input
		);
		$html = $newBlockdiag->showImage();

		return $html;
	}

}

/**
 * Blockdiag
 **/
class Blockdiag {
        private $_path_array = array(
           'blockdiag' => '/usr/local/bin/blockdiag',
           'seqdiag' => '/usr/local/bin/seqdiag',
           'actdiag' => '/usr/local/bin/actdiag',
           'nwdiag' => '/usr/local/bin/nwdiag',
           'rackdiag' => '/usr/local/bin/rackdiag',     # in nwdiag
           'packetdiag' => '/usr/local/bin/packetdiag', # in nwdiag
           );
	private $_imgType = 'png';
	private $_hash;
	private $_source;
	private $_tmpDir;
	private $_blockdiagDir;
	private $_blockdiagUrl;

	public function __construct( $blockdiagDir, $blockdiagUrl, $tmpDir, $source )
	{
		$this->_blockdiagDir  = $blockdiagDir;
		$this->_blockdiagUrl  = $blockdiagUrl;
		$this->_tmpDir        = $tmpDir;
		$this->_hash          = md5( $source );
		$this->_source        = $source;
	}
	
	public function showImage() {
		if( file_exists( $this->_getImagePath() ) ) {
			$html = $this->_mkImageTag();
		} else {
			$html = $this->_generate();
		}

		return $html;
	}

	private function _generate() {
                if (preg_match('/^\s*(\w+)\s*{/', $this->_source, $matches)) {
                   $diagram_type = $matches[1];
                } else { 
                   #return $this->_error("diagtype is not specified.");
                   $diagram_type = 'blockdiag'; # blockdiag for default
                }
                
                $diagprog_path = $this->_path_array[$diagram_type];
        	if (!is_file($diagprog_path))
        	{
        	    return $this->_error("$diagram_type is not found at the specified place.");
		}

		// temporary directory check
		if( !file_exists( $this->_tmpDir ) ){
			if( !wfMkdirParents( $this->_tmpDir ) ) {
				return $this->_error( 'temporary directory is not found.' );
			}
		} elseif ( !is_dir( $this->_tmpDir )  ){
			return $this->_error( 'temporary directory is not directory' );
		} elseif ( !is_writable( $this->_tmpDir ) ){
			return $this->_error( 'temporary directory is not writable' );
		}

		// create temporary file
		$dstTmpName = tempnam( $this->_tmpDir, 'blockdiag' );
		$srcTmpName = tempnam( $this->_tmpDir, 'blockdiag' );

		// write blockdiag source
		$fp = fopen( $srcTmpName, 'w');
		fwrite($fp, $this->_source);
		fclose($fp);
		
		// generate blockdiag image
		$cmd = $diagprog_path . ' -T ' .
			escapeshellarg( $this->_imgType ) . ' -o ' .
			escapeshellarg( $dstTmpName ) . ' ' .
			escapeshellarg( $srcTmpName );

		$res = `$cmd`;

		if( filesize( $dstTmpName ) == 0 ) {
			return $this->_error( 'unknown error.' );
		}

		// move to image directory
		$hashpath = $this->_getHashPath();
		if( !file_exists( $hashpath ) ) {
			if( !@wfMkdirParents( $hashpath, 0755 ) ) {
				return $this->_error( 'can not make blockdiag image directory', $this->_blockdiagDir );
			}
		} elseif( !is_dir( $hashpath ) ) {
			return $this->_error( 'blockdiag image directory is already exists. but not directory' );
		} elseif( !is_writable( $hashpath ) ) {
			return $this->_error( 'blockdiag image directory is not writable' );
		}

		if( !rename( "$dstTmpName", "$hashpath/{$this->_hash}.png" ) ) {
			return $this->_error( 'can not rename blockdiag image' );
		}

		return $this->_mkImageTag();
	}
	
	private function _mkImageTag() {
		$url = $this->_getImageUrl();

		return Xml::element(
		       	'img',
			array( 
				'class' => 'blockdiag',
				'src' => $url,
			)
		);	
	}

	private function _getImageUrl() {
		return "{$this->_blockdiagUrl}/{$this->_getHashSubPath()}/{$this->_hash}.png";
	}

	private function _getImagePath() {
		return "{$this->_blockdiagDir}/{$this->_getHashSubPath()}/{$this->_hash}.png";
	}

	private function _getHashPath() {
		return "{$this->_blockdiagDir}/{$this->_getHashSubPath()}";
	}
	
	private function _getHashSubPath() {
		return substr($this->_hash, 0, 1)
					.'/'. substr($this->_hash, 1, 1)
					.'/'. substr($this->_hash, 2, 1);
	}

	private function _error( $msg, $append = '' ) {
		$mf     = 'blockdiag';
		$errmsg = htmlspecialchars( $msg . ' ' . $append );
		return "<strong class='error'>$mf ($errmsg)</strong>\n";
	}
}

