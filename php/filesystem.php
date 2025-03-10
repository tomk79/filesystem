<?php
/**
 * tomk79/filesystem
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */

namespace tomk79;

/**
 * tomk79/filesystem core class
 *
 * @author Tomoya Koyanagi <tomk79@gmail.com>
 */
class filesystem{

	/**
	 * ファイルおよびディレクトリ操作時のデフォルトパーミッション
	 */
	private $default_permission = array('dir'=>0775,'file'=>0775);
	/**
	 * ファイルシステムの文字セット
	 */
	private $filesystem_encoding = null;

	/**
	 * コンストラクタ
	 *
	 * @param object $conf 設定オブジェクト
	 */
	public function __construct($conf=null){
		$conf = json_decode( json_encode($conf), true );
		if(!is_array($conf)){
			$conf = array();
		}
		if( array_key_exists('file_default_permission', $conf) && strlen( $conf['file_default_permission'] ?? '' ) ){
			$this->default_permission['file'] = octdec( $conf['file_default_permission'] );
		}
		if( array_key_exists('dir_default_permission', $conf) && strlen( $conf['dir_default_permission'] ?? '' ) ){
			$this->default_permission['dir'] = octdec( $conf['dir_default_permission'] );
		}
	}

	/**
	 * 書き込み/上書きしてよいアイテムか検証する。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool 書き込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_writable( $path ){
		$path = $this->localize_path($path);
		if( !$this->is_file($path) ){
			return is_writable( dirname($path) );
		}
		return is_writable( $path );
	}

	/**
	 * 読み込んでよいアイテムか検証する。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool 読み込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_readable( $path ){
		$path = $this->localize_path($path);
		return is_readable( $path );
	}

	/**
	 * ファイルが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルが存在する場合 `true`、存在しない場合、またはディレクトリが存在する場合に `false` を返します。
	 */
	public function is_file( $path ){
		$path = $this->localize_path($path);
		return is_file( $path );
	}

	/**
	 * シンボリックリンクかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルがシンボリックリンクの場合 `true`、存在しない場合、それ以外の場合に `false` を返します。
	 */
	public function is_link( $path ){
		$path = $this->localize_path($path);
		return is_link( $path );
	}

	/**
	 * ディレクトリが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ディレクトリが存在する場合 `true`、存在しない場合、またはファイルが存在する場合に `false` を返します。
	 */
	public function is_dir( $path ){
		$path = $this->localize_path($path);
		return is_dir( $path );
	}

	/**
	 * ファイルまたはディレクトリが存在するかどうか調べる。
	 *
	 * @param string $path 検証対象のパス
	 * @return bool ファイルまたはディレクトリが存在する場合 `true`、存在しない場合に `false` を返します。
	 */
	public function file_exists( $path ){
		$path = $this->localize_path($path);
		return file_exists( $path );
	}

	/**
	 * ディレクトリを作成する。
	 *
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir( $dirpath , $perm = null ){
		$dirpath = $this->localize_path($dirpath);

		if( $this->is_dir( $dirpath ) ){
			// 既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return true;
		}
		if( !$this->is_dir( dirname($dirpath) ) ){
			// 親ディレクトリが存在しない場合は、作成できない
			return false;
		}
		if( !$this->is_writable( dirname($dirpath) ) ){
			// 親ディレクトリに書き込みできない場合は、作成できない
			return false;
		}
		$result = mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		clearstatcache();
		return	$result;
	}

	/**
	 * ディレクトリを作成する(上層ディレクトリも全て作成)
	 *
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir_r( $dirpath , $perm = null ){
		$dirpath = $this->localize_path($dirpath);
		if( $this->is_dir( $dirpath ) ){
			return true;
		}
		if( $this->is_file( $dirpath ) ){
			return false;
		}
		$patharray = explode( DIRECTORY_SEPARATOR , $this->localize_path( $this->get_realpath($dirpath) ) );
		$targetpath = '';
		foreach( $patharray as $idx=>$Line ){
			if( !strlen( $Line ?? '' ) || $Line == '.' || $Line == '..' ){ continue; }
			if(!($idx===0 && DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-zA-Z]\:$/s', $Line ?? ''))){
				$targetpath .= DIRECTORY_SEPARATOR;
			}
			$targetpath .= $Line;

			// clearstatcache();
			if( !$this->is_dir( $targetpath ) ){
				$targetpath = $this->localize_path( $targetpath );
				if( !$this->mkdir( $targetpath , $perm ) ){
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * ファイルやディレクトリを中身ごと完全に削除する。
	 *
	 * このメソッドは、ファイルやシンボリックリンクも削除します。
	 * ディレクトリを削除する場合は、中身ごと完全に削除します。
	 * シンボリックリンクは、その先を追わず、シンボリックリンク本体のみを削除します。
	 *
	 * @param string $path 対象のパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rm( $path ){
		$path = $this->localize_path($path);
		clearstatcache();

		if( !$this->is_writable( $path ) ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			return	$result;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			$flist = $this->ls( $path );
			if( is_array($flist) ){
				foreach ( $flist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					$this->rm( $path.DIRECTORY_SEPARATOR.$Line );
				}
			}
			$result = rmdir( $path );
			return	$result;

		}

		return false;
	}

	/**
	 * ディレクトリを削除する。
	 *
	 * このメソッドはディレクトリを削除します。
	 * 中身のない、空のディレクトリ以外は削除できません。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rmdir( $path ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			// ディレクトリ以外は削除できません。
			return false;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			// rmdir() は再帰的削除を行いません。
			// 再帰的に削除したい場合は、代わりに `rm()` または `rmdir_r()` を使用します。
			return @rmdir( $path );
		}

		return false;
	}//rmdir()

	/**
	 * ディレクトリを再帰的に削除する。
	 *
	 * このメソッドはディレクトリを再帰的に削除します。
	 * 中身のない、空のディレクトリ以外は削除できません。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function rmdir_r( $path ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){
			return false;
		}
		if( $this->is_file( $path ) || $this->is_link( $path ) ){
			// ファイルまたはシンボリックリンクの場合の処理
			// ディレクトリ以外は削除できません。
			return false;

		}elseif( $this->is_dir( $path ) ){
			// ディレクトリの処理
			$filelist = $this->ls($path);
			if( is_array($filelist) ){
				foreach( $filelist as $basename ){
					if( $this->is_file( $path.DIRECTORY_SEPARATOR.$basename ) ){
						$this->rm( $path.DIRECTORY_SEPARATOR.$basename );
					}else if( !$this->rmdir_r( $path.DIRECTORY_SEPARATOR.$basename ) ){
						return false;
					}
				}
			}
			return $this->rmdir( $path );
		}

		return false;
	}//rmdir_r()


	/**
	 * ファイルを上書き保存する。
	 *
	 * このメソッドは、`$filepath` にデータを保存します。
	 * もともと保存されていた内容は破棄され、新しいデータで上書きします。
	 *
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function save_file( $filepath , $content , $perm = null ){
		$filepath = $this->get_realpath($filepath);
		$filepath = $this->localize_path($filepath);

		if( $this->is_dir( $filepath ) ){
			return false;
		}
		if( !$this->is_writable( $filepath ) ){
			return false;
		}

		if( !strlen( $content ?? '' ) ){
			// 空白のファイルで上書きしたい場合
			if( $this->is_file( $filepath ) ){
				@unlink( $filepath );
			}
			@touch( $filepath );
			$this->chmod( $filepath , $perm );
			clearstatcache();
			return $this->is_file( $filepath );
		}

		clearstatcache();
		$fp = fopen( $filepath, 'w' );
		if( !is_resource( $fp ) ){
			return false;
		}

		for ($written = 0; $written < strlen($content); $written += $fwrite) {
			$fwrite = fwrite($fp, substr($content, $written));
			if ($fwrite === false) {
				break;
			}
		}

		fclose($fp);

		$this->chmod( $filepath , $perm );
		clearstatcache();
		return !empty( $written );
	}//save_file()

	/**
	 * ファイルの中身を文字列として取得する。
	 *
	 * @param string $path ファイルのパス
	 * @return string ファイル `$path` の内容
	 */
	public function read_file( $path ){
		$path = $this->localize_path($path);
		return file_get_contents( $path );
	}//file_get_contents()

	/**
	 * ファイルの更新日時を比較する。
	 *
	 * @param string $path_a 比較対象A
	 * @param string $path_b 比較対象B
	 * @return bool|null
	 * `$path_a` の方が新しかった場合に `true`、
	 * `$path_b` の方が新しかった場合に `false`、
	 * 同時だった場合に `null` を返します。
	 *
	 * いずれか一方、または両方のファイルが存在しない場合、次のように振る舞います。
	 * - 両方のファイルが存在しない場合 = `null`
	 * - $path_a が存在せず、$path_b は存在する場合 = `false`
	 * - $path_a が存在し、$path_b は存在しない場合 = `true`
	 */
	public function is_newer_a_than_b( $path_a , $path_b ){
		$path_a = $this->localize_path($path_a);
		$path_b = $this->localize_path($path_b);

		// 比較できない場合に
		if(!file_exists($path_a) && !file_exists($path_b)){return null;}
		if(!file_exists($path_a)){return false;}
		if(!file_exists($path_b)){return true;}

		$mtime_a = filemtime( $path_a );
		$mtime_b = filemtime( $path_b );
		if( $mtime_a > $mtime_b ){
			return true;
		}elseif( $mtime_a < $mtime_b ){
			return false;
		}
		return null;
	}//is_newer_a_than_b()

	/**
	 * ファイル名/ディレクトリ名を変更する。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename( $original , $newname ){
		$original = $this->localize_path($original);
		$newname  = $this->localize_path($newname );

		if( !file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		return rename( $original , $newname );
	}//rename()

	/**
	 * ファイル名/ディレクトリ名を強制的に変更する。
	 *
	 * 移動先の親ディレクトリが存在しない場合にも、親ディレクトリを作成して移動するよう試みます。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename_f( $original , $newname ){
		$original = $this->localize_path($original);
		$newname  = $this->localize_path($newname );

		if( !file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		$dirname = dirname( $newname );
		if( !$this->is_dir( $dirname ) ){
			if( !$this->mkdir_r( $dirname ) ){
				return false;
			}
		}
		return rename( $original , $newname );
	} // rename_f()

	/**
	 * 絶対パスを得る。
	 *
	 * パス情報を受け取り、スラッシュから始まるサーバー内部絶対パスに変換して返します。
	 *
	 * このメソッドは、PHPの `realpath()` と異なり、存在しないパスも絶対パスに変換します。
	 *
	 * @param string $path 対象のパス
	 * @param string $cd カレントディレクトリパス。
	 * 実在する有効なディレクトリのパス、または絶対パスの表現で指定される必要があります。
	 * 省略時、カレントディレクトリを自動採用します。
	 * @return string 絶対パス
	 */
	public function get_realpath( $path, $cd = '.' ){
		$is_dir = false;
		if( preg_match( '/(\/|\\\\)+$/s', $path ?? '' ) ){
			$is_dir = true;
		}
		$path = $this->localize_path($path);
		if( is_null($cd) ){ $cd = '.'; }
		$cd = $this->localize_path($cd);
		$preg_dirsep = preg_quote(DIRECTORY_SEPARATOR, '/');

		if( $this->is_dir($cd) ){
			$cd = realpath($cd);
		}elseif( !preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $cd ?? '') ){
			$cd = false;
		}
		if( $cd === false ){
			return false;
		}

		$prefix = '';
		$localpath = $path;
		if( preg_match('/^((?:[A-Za-z]\\:'.$preg_dirsep.')|'.$preg_dirsep.'{1,2})(.*?)$/', $path ?? '', $matched) ){
			// もともと絶対パスの指定か調べる
			$prefix = preg_replace('/'.$preg_dirsep.'$/', '', $matched[1]);
			$localpath = $matched[2];
			$cd = null; // 元の指定が絶対パスだったら、カレントディレクトリは関係ないので捨てる。
		}

		$path = $cd.DIRECTORY_SEPARATOR.'.'.DIRECTORY_SEPARATOR.$localpath;

		if( file_exists( $prefix.$path ) ){
			$rtn = realpath( $prefix.$path );
			if( $is_dir && $rtn != realpath('/') ){
				$rtn .= DIRECTORY_SEPARATOR;
			}
			return $rtn;
		}

		$paths = explode( DIRECTORY_SEPARATOR, $path );
		$path = '';
		foreach( $paths as $idx=>$row ){
			if( $row == '' || $row == '.' ){
				continue;
			}
			if( $row == '..' ){
				$path = dirname($path);
				if($path == DIRECTORY_SEPARATOR || preg_match('/^[a-zA-Z]\:[\\/\\\\]*$/s', $path) ){
					$path ='';
				}
				continue;
			}
			if(!($idx===0 && DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-zA-Z]\:$/s', $row ?? ''))){
				$path .= DIRECTORY_SEPARATOR;
			}
			$path .= $row;
		}

		$rtn = $prefix.$path;
		if( $is_dir ){
			$rtn .= DIRECTORY_SEPARATOR;
		}
		return $rtn;
	}

	/**
	 * 相対パスを得る。
	 *
	 * パス情報を受け取り、ドットスラッシュから始まる相対絶対パスに変換して返します。
	 *
	 * @param string $path 対象のパス
	 * @param string $cd カレントディレクトリパス。
	 * 実在する有効なディレクトリのパス、または絶対パスの表現で指定される必要があります。
	 * 省略時、カレントディレクトリを自動採用します。
	 * @return string 相対パス
	 */
	public function get_relatedpath( $path, $cd = '.' ){
		$is_dir = false;
		if( preg_match( '/(\/|\\\\)+$/s', $path ?? '' ) ){
			$is_dir = true;
		}
		if( !strlen( $cd ?? '' ) ){
			$cd = realpath('.');
		}elseif( $this->is_dir($cd) ){
			$cd = realpath($cd);
		}elseif( $this->is_file($cd) ){
			$cd = realpath(dirname($cd));
		}
		$path = $this->get_realpath($path, $cd);

		$normalize = function( $tmp_path, $fs ){
			$tmp_path = $fs->localize_path( $tmp_path );
			$preg_dirsep = preg_quote(DIRECTORY_SEPARATOR, '/');
			if( DIRECTORY_SEPARATOR == '\\' ){
				$tmp_path = preg_replace( '/^[a-zA-Z]\:/s', '', $tmp_path ?? '' );
			}
			$tmp_path = preg_replace( '/^('.$preg_dirsep.')+/s', '', $tmp_path ?? '' );
			$tmp_path = preg_replace( '/('.$preg_dirsep.')+$/s', '', $tmp_path ?? '' );
			if( strlen($tmp_path) ){
				$tmp_path = explode( DIRECTORY_SEPARATOR, $tmp_path );
			}else{
				$tmp_path = array();
			}

			return $tmp_path;
		};

		$cd = $normalize($cd, $this);
		$path = $normalize($path, $this);

		$rtn = array();
		while( 1 ){
			if( !count($cd) || !count($path) ){
				break;
			}
			if( $cd[0] === $path[0] ){
				array_shift( $cd );
				array_shift( $path );
				continue;
			}
			break;
		}
		if( count($cd) ){
			foreach($cd as $dirname){
				array_push( $rtn, '..' );
			}
		}else{
			array_push( $rtn, '.' );
		}
		$rtn = array_merge( $rtn, $path );
		$rtn = implode( DIRECTORY_SEPARATOR, $rtn );

		if( $is_dir ){
			$rtn .= DIRECTORY_SEPARATOR;
		}
		return $rtn;
	}

	/**
	 * パス情報を得る。
	 *
	 * @param string $path 対象のパス
	 * @return array パス情報
	 */
	public function pathinfo( $path ){
		if(strpos($path,'#')!==false){ list($path, $hash) = explode( '#', $path, 2 ); }
		if(strpos($path,'?')!==false){ list($path, $query) = explode( '?', $path, 2 ); }

		$pathinfo = pathinfo( $path );
		$pathinfo['filename'] = $this->trim_extension( $pathinfo['basename'] );
		$pathinfo['extension'] = $this->get_extension( $pathinfo['basename'] );
		$pathinfo['query'] = (isset($query)&&strlen($query) ? '?'.$query : null);
		$pathinfo['hash'] = (isset($hash)&&strlen($hash) ? '#'.$hash : null);
		return $pathinfo;
	}

	/**
	 * パス情報から、ファイル名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 抜き出されたファイル名
	 */
	public function get_basename( $path ){
		$path = pathinfo( $path , PATHINFO_BASENAME );
		if( !strlen($path ?? '') ){$path = null;}
		return $path;
	}

	/**
	 * パス情報から、拡張子を除いたファイル名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 拡張子が除かれたパス
	 */
	public function trim_extension( $path ){
		$pathinfo = pathinfo( $path );
		if( !array_key_exists('extension', $pathinfo) ){
			$pathinfo['extension'] = '';
		}
		$RTN = preg_replace( '/\.'.preg_quote( $pathinfo['extension'], '/' ).'$/' , '' , $path ?? '' );
		return $RTN;
	}

	/**
	 * ファイル名を含むパス情報から、ファイルが格納されているディレクトリ名を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 親ディレクトリのパス
	 */
	public function get_dirpath( $path ){
		$path = pathinfo( $path , PATHINFO_DIRNAME );
		if( !strlen($path ?? '') ){$path = null;}
		return $path;
	}

	/**
	 * パス情報から、拡張子を取得する。
	 *
	 * @param string $path 対象のパス
	 * @return string 拡張子
	 */
	public function get_extension( $path ){
		if( is_null($path) ){ return null; }
		$path = preg_replace('/\#.*$/si', '', $path);
		$path = preg_replace('/\?.*$/si', '', $path);
		$path = pathinfo( $path , PATHINFO_EXTENSION );
		if(!strlen($path ?? '')){$path = null;}
		return $path;
	}


	/**
	 * CSVファイルを読み込む。
	 *
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * - delimiter = 区切り文字(省略時、カンマ)
	 * - enclosure = クロージャー文字(省略時、ダブルクオート)
	 * - escape = エスケープ文字(省略時、ダブルクオート)
	 * - size = 一度に読み込むサイズ(省略時、10000)
	 * - charset = 文字セット(省略時、UTF-8)
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv( $path , $options = array() ){
		// $options['charset'] は、保存されているCSVファイルの文字エンコードです。
		// 省略時は UTF-8 から、内部エンコーディングに変換します。

		$path = $this->localize_path($path);

		if( !$this->is_file( $path ) ){
			// ファイルがなければfalseを返す
			return false;
		}

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !isset($options['delimiter']) || !strlen( $options['delimiter'] ?? '' ) )    { $options['delimiter'] = ','; }
		if( !isset($options['enclosure']) || !strlen( $options['enclosure'] ?? '' ) )    { $options['enclosure'] = '"'; }
		if( !isset($options['escape'])    || !strlen( $options['escape'] ?? '' ) )       { $options['escape'] = PHP_VERSION_ID >= 80000 ? "" : "\\"; } // NOTE: PHP 7 系では、空文字列を指定するとエラーになる。
		if( !isset($options['size'])      || !strlen( $options['size'] ?? '' ) )         { $options['size'] = 0; }
		if( !isset($options['charset'])   || !strlen( $options['charset'] ?? '' ) )      { $options['charset'] = 'UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP'; }//←CSVの文字セット

		$RTN = array();
		$fp = fopen( $path, 'r' );
		if( !is_resource( $fp ) ){
			return false;
		}

		while( $SMMEMO = fgetcsv( $fp, intval( $options['size'] ), $options['delimiter'], $options['enclosure'], $options['escape'] ) ){
			foreach( $SMMEMO as $key=>$row ){
				$SMMEMO[$key] = mb_convert_encoding( $row ?? '' , mb_internal_encoding(), $options['charset'] );
			}
			array_push( $RTN , $SMMEMO );
		}
		fclose($fp);
		return $RTN;
	} // read_csv()

	/**
	 * 配列をCSV形式に変換する。
	 *
	 * 改行コードはLFで出力されます。
	 *
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * - charset = 文字セット(省略時、UTF-8)
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv( $array , $options = array() ){
		// $options['charset'] は、出力されるCSV形式の文字エンコードを指定します。
		// 省略時は UTF-8 に変換して返します。
		if( !is_array( $array ) ){ $array = array(); }

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'charset', $options ) ){
			$options['charset'] = null;
		}
		if( !isset($options['charset']) || !strlen( $options['charset'] ?? '' ) ){
			$options['charset'] = 'UTF-8';
		}

		$RTN = '';
		foreach( $array as $Line ){
			if( is_null( $Line ) ){ continue; }
			if( !is_array( $Line ) ){ $Line = array(); }
			foreach( $Line as $cell ){
				$cell = mb_convert_encoding( $cell ?? '' , $options['charset']);
				if( preg_match( '/"/' , $cell ?? '' ) ){
					$cell = preg_replace( '/"/' , '""' , $cell ?? '');
				}
				if( strlen( $cell ) ){
					$cell = '"'.$cell.'"';
				}
				$RTN .= $cell.',';
			}
			$RTN = preg_replace( '/,$/' , '' , $RTN );
			$RTN .= "\n";
		}
		return $RTN;
	} // mk_csv()

	/**
	 * ファイルを複製する。
	 *
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy( $from , $to , $perm = null ){
		$from = $this->localize_path($from);
		$to   = $this->localize_path($to  );

		if( !$this->is_file( $from ) ){
			return false;
		}
		if( !$this->is_readable( $from ) ){
			return false;
		}

		if( $this->is_file( $to ) ){
			//	まったく同じファイルだった場合は、複製しないでtrueを返す。
			if( md5_file( $from ) == md5_file( $to ) && filesize( $from ) == filesize( $to ) ){
				return true;
			}
		}
		if( !@copy( $from , $to ) ){
			return false;
		}
		$this->chmod( $to , $perm );
		return true;
	}//copy()

	/**
	 * ディレクトリを再帰的に複製する(下層ディレクトリも全てコピー)
	 *
	 * ディレクトリを、含まれる内容ごと複製します。
	 * 受け取ったパスがファイルの場合は、単体のファイルが複製されます。
	 *
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy_r( $from , $to , $perm = null ){
		$from = $this->localize_path($from);
		$to   = $this->localize_path($to  );

		$result = true;

		if( $this->is_file( $from ) ){
			if( $this->mkdir_r( dirname( $to ) ) ){
				if( !$this->copy( $from , $to , $perm ) ){
					$result = false;
				}
			}else{
				$result = false;
			}
		}elseif( $this->is_dir( $from ) ){
			if( !$this->is_dir( $to ) ){
				if( !$this->mkdir_r( $to ) ){
					$result = false;
				}
			}
			$itemlist = $this->ls( $from );
			if( is_array($itemlist) ){
				foreach( $itemlist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					if( $this->is_dir( $from.DIRECTORY_SEPARATOR.$Line ) ){
						if( $this->is_file( $to.DIRECTORY_SEPARATOR.$Line ) ){
							continue;
						}elseif( !$this->is_dir( $to.DIRECTORY_SEPARATOR.$Line ) ){
							if( !$this->mkdir_r( $to.DIRECTORY_SEPARATOR.$Line ) ){
								$result = false;
							}
						}
						if( !$this->copy_r( $from.DIRECTORY_SEPARATOR.$Line , $to.DIRECTORY_SEPARATOR.$Line , $perm ) ){
							$result = false;
						}
						continue;
					}elseif( $this->is_file( $from.DIRECTORY_SEPARATOR.$Line ) ){
						if( !$this->copy_r( $from.DIRECTORY_SEPARATOR.$Line , $to.DIRECTORY_SEPARATOR.$Line , $perm ) ){
							$result = false;
						}
						continue;
					}
				}
			}
		}

		return $result;
	} // copy_r()

	/**
	 * パーミッションを変更する。
	 *
	 * @param string $filepath 対象のパス
	 * @param int $perm 与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod( $filepath, $perm = null ){
		$filepath = $this->localize_path($filepath);
		if( !file_exists($filepath) ){
			return;
		}

		if( is_null( $perm ) ){
			if( $this->is_dir( $filepath ) ){
				$perm = $this->default_permission['dir'];
			}else{
				$perm = $this->default_permission['file'];
			}
		}
		if( is_null( $perm ) ){
			$perm = 0775; // コンフィグに設定モレがあった場合
		}
		return chmod( $filepath , $perm );
	} // chmod()

	/**
	 * パーミッションを再帰的に変更する。(下層のファイルやディレクトリも全て)
	 *
	 * `$perm_file` と `$perm_dir` が省略された場合は、代わりに初期化時に登録されたデフォルトのパーミッションが与えられます。
	 *
	 * 第2引数 `$perm_dir` が省略され、最初の引数 `$perm_file` だけが与えられた場合は、 ファイルとディレクトリの両方に `$perm_file` が適用されます。
	 *
	 * @param string $filepath 対象のパス
	 * @param int $perm_file ファイルに与えるパーミッション (省略可)
	 * @param int $perm_dir ディレクトリに与えるパーミッション (省略可)
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod_r( $filepath, $perm_file = null, $perm_dir = null ){
		$filepath = $this->localize_path($filepath);
		if( !is_null( $perm_file ) && is_null($perm_dir) ){
			// パーミッション設定値が1つだけ与えられた場合には、
			// ファイルにもディレクトリにも適用する。
			$perm_dir = $perm_file;
		}

		$result = true;

		if( $this->is_file( $filepath ) ){
			if( !$this->chmod( $filepath, $perm_file ) ){
				$result = false;
			}
		}elseif( $this->is_dir( $filepath ) ){
			$itemlist = $this->ls( $filepath );
			if( !$this->chmod( $filepath, $perm_dir ) ){
				$result = false;
			}
			if( is_array($itemlist) ){
				foreach( $itemlist as $Line ){
					if( $Line == '.' || $Line == '..' ){ continue; }
					if( !$this->chmod_r( $filepath.DIRECTORY_SEPARATOR.$Line, $perm_file, $perm_dir ) ){
						$result = false;
					}
					continue;
				}
			}
		}

		return $result;
	}


	/**
	 * パーミッション情報を調べ、3桁の数字で返す。
	 *
	 * @param string $path 対象のパス
	 * @return int|bool 成功時に 3桁の数字、失敗時に `false` を返します。
	 */
	public function get_permission( $path ){
		$path = $this->localize_path($path);

		if( !file_exists( $path ) ){
			return false;
		}
		$perm = rtrim( sprintf( "%o\n" , fileperms( $path ) ) );
		$start = strlen( $perm ) - 3;
		return substr( $perm , $start , 3 );
	}


	/**
	 * ディレクトリにあるファイル名のリストを配列で返す。
	 *
	 * @param string $path 対象ディレクトリのパス
	 * @return array|bool 成功時にファイルまたはディレクトリ名の一覧を格納した配列、失敗時に `false` を返します。
	 */
	public function ls($path){
		$path = $this->localize_path($path);

		if( $path === false ){ return false; }
		if( !file_exists( $path ) ){ return false; }
		if( !$this->is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = @opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			// CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		usort($RTN, "strnatcmp");
		return	$RTN;
	}//ls()

	/**
	 * ディレクトリの内部を比較し、$comparisonに含まれない要素を$targetから削除する。
	 *
	 * @param string $target クリーニング対象のディレクトリパス
	 * @param string $comparison 比較するディレクトリのパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function compare_and_cleanup( $target , $comparison ){
		if( is_null( $comparison ) || is_null( $target ) ){ return false; }

		$target = $this->localize_path($target);
		$comparison = $this->localize_path($comparison);

		if( !file_exists( $comparison ) && file_exists( $target ) ){
			$this->rm( $target );
			return true;
		}

		if( $this->is_dir( $target ) ){
			$flist = $this->ls( $target );
		}else{
			return true;
		}

		if( is_array($flist) ){
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->compare_and_cleanup( $target.DIRECTORY_SEPARATOR.$Line , $comparison.DIRECTORY_SEPARATOR.$Line );
			}
		}

		return true;
	}//compare_and_cleanup()

	/**
	 * ディレクトリを同期する。
	 *
	 * @param string $path_sync_from 同期元ディレクトリ
	 * @param string $path_sync_to 同期先ディレクトリ
	 * @return bool 常に `true` を返します。
	 */
	public function sync_dir( $path_sync_from , $path_sync_to ){
		$this->copy_r( $path_sync_from , $path_sync_to );
		$this->compare_and_cleanup( $path_sync_to , $path_sync_from );
		return true;
	}//sync_dir()

	/**
	 * 指定されたディレクトリ以下の、全ての空っぽのディレクトリを削除する。
	 *
	 * @param string $path ディレクトリパス
	 * @param array $options オプション
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function remove_empty_dir( $path , $options = array() ){
		$path = $this->localize_path($path);

		if( !$this->is_writable( $path ) ){ return false; }
		if( !$this->is_dir( $path ) ){ return false; }
		if( $this->is_file( $path ) || $this->is_link( $path ) ){ return false; }
		$path = @realpath( $path );
		if( $path === false ){ return false; }

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'depth', $options ) ){
			$options['depth'] = null;
		}

		// --------------------------------------
		// 次の階層を処理するかどうかのスイッチ
		$switch_donext = false;
		if( is_null( $options['depth'] ) ){
			// 深さの指定がなければ掘る
			$switch_donext = true;
		}elseif( !is_int( $options['depth'] ) ){
			// 指定がnullでも数値でもなければ掘らない
			$switch_donext = false;
		}elseif( $options['depth'] <= 0 ){
			// 指定がゼロ以下なら、今回の処理をして終了
			$switch_donext = false;
		}elseif( $options['depth'] > 0 ){
			// 指定が正の数(ゼロは含まない)なら、掘る
			$options['depth'] --;
			$switch_donext = true;
		}else{
			return false;
		}
		// / 次の階層を処理するかどうかのスイッチ
		// --------------------------------------

		$flist = $this->ls( $path );
		if( !count( $flist ) ){
			// 開いたディレクトリの中身が
			// "." と ".." のみだった場合
			// 削除して終了
			$result = @rmdir( $path );
			return	$result;
		}
		$alive = false;
		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( $this->is_link( $path.DIRECTORY_SEPARATOR.$Line ) ){
				// シンボリックリンクは無視する。
			}elseif( $this->is_dir( $path.DIRECTORY_SEPARATOR.$Line ) ){
				if( $switch_donext ){
					// さらに掘れと指令があれば、掘る。
					$this->remove_empty_dir( $path.DIRECTORY_SEPARATOR.$Line , $options );
				}
			}
			if( file_exists( $path.DIRECTORY_SEPARATOR.$Line ) ){
				$alive = true;
			}
		}
		if( !$alive ){
			$result = @rmdir( $path );
			return	$result;
		}
		return true;
	}//remove_empty_dir()


	/**
	 * 指定された2つのディレクトリの内容を比較し、まったく同じかどうか調べる。
	 *
	 * @param string $dir_a 比較対象ディレクトリA
	 * @param string $dir_b 比較対象ディレクトリB
	 * @param array $options オプション
	 * <dl>
	 *   <dt>bool $options['compare_filecontent']</dt>
	 * 	   <dd>ファイルの中身も比較するか？</dd>
	 *   <dt>bool $options['compare_emptydir']</dt>
	 * 	   <dd>空っぽのディレクトリの有無も評価に含めるか？</dd>
	 * </dl>
	 * @return bool 同じ場合に `true`、異なる場合に `false` を返します。
	 */
	public function compare_dir( $dir_a , $dir_b , $options = array() ){
		if( ( $this->is_file( $dir_a ) && !$this->is_file( $dir_b ) ) || ( !$this->is_file( $dir_a ) && $this->is_file( $dir_b ) ) ){
			return false;
		}
		if( ( ( $this->is_dir( $dir_a ) && !$this->is_dir( $dir_b ) ) || ( !$this->is_dir( $dir_a ) && $this->is_dir( $dir_b ) ) ) && $options['compare_emptydir'] ){
			return false;
		}

		// Normalize $options
		if( !is_array($options) ){
			$options = array();
		}
		if( !array_key_exists( 'compare_filecontent', $options ) ){
			$options['compare_filecontent'] = null;
		}
		if( !array_key_exists( 'compare_emptydir', $options ) ){
			$options['compare_emptydir'] = null;
		}

		if( $this->is_file( $dir_a ) && $this->is_file( $dir_b ) ){
			// --------------------------------------
			// 両方ファイルだったら
			if( $options['compare_filecontent'] ){
				// ファイルの内容も比較する設定の場合、
				// それぞれファイルを開いて同じかどうかを比較
				$filecontent_a = $this->read_file( $dir_a );
				$filecontent_b = $this->read_file( $dir_b );
				if( $filecontent_a !== $filecontent_b ){
					return false;
				}
			}
			return true;
		}

		if( $this->is_dir( $dir_a ) || $this->is_dir( $dir_b ) ){
			// --------------------------------------
			// 両方ディレクトリだったら
			$contlist_a = $this->ls( $dir_a );
			$contlist_b = $this->ls( $dir_b );

			if( $options['compare_emptydir'] && $contlist_a !== $contlist_b ){
				// 空っぽのディレクトリも厳密に評価する設定で、
				// ディレクトリ内の要素配列の内容が異なれば、false。
				return false;
			}

			$done = array();
			foreach( $contlist_a as $Line ){
				// Aをチェック
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.DIRECTORY_SEPARATOR.$Line , $dir_b.DIRECTORY_SEPARATOR.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

			foreach( $contlist_b as $Line ){
				// Aに含まれなかったBをチェック
				if( $done[$Line] ){ continue; }
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.DIRECTORY_SEPARATOR.$Line , $dir_b.DIRECTORY_SEPARATOR.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

		}

		return true;
	}//compare_dir()


	/**
	 * サーバがUNIXパスか調べる。
	 *
	 * @return bool UNIXパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_unix(){
		if( DIRECTORY_SEPARATOR == '/' ){
			return true;
		}
		return false;
	}//is_unix()

	/**
	 * サーバがWindowsパスか調べる。
	 *
	 * @return bool Windowsパスなら `true`、それ以外なら `false` を返します。
	 */
	public function is_windows(){
		if( DIRECTORY_SEPARATOR == '\\' ){
			return true;
		}
		return false;
	}//is_windows()


	/**
	 * パスを正規化する。
	 *
	 * 受け取ったパスを、スラッシュ区切りの表現に正規化します。
	 * Windowsのボリュームラベルが付いている場合は削除します。
	 * URIスキーム(http, https, ftp など) で始まる場合、2つのスラッシュで始まる場合(`//www.example.com/abc/` など)、これを残して正規化します。
	 *
	 *  - 例： `\a\b\c.html` → `/a/b/c.html` バックスラッシュはスラッシュに置き換えられます。
	 *  - 例： `/a/b////c.html` → `/a/b/c.html` 余計なスラッシュはまとめられます。
	 *  - 例： `C:\a\b\c.html` → `/a/b/c.html` ボリュームラベルは削除されます。
	 *  - 例： `http://a/b/c.html` → `http://a/b/c.html` URIスキームは残されます。
	 *  - 例： `//a/b/c.html` → `//a/b/c.html` ドメイン名は残されます。
	 *
	 * @param string $path 正規化するパス
	 * @return string 正規化されたパス
	 */
	public function normalize_path($path){
		if( is_null($path) ){ return null; }
		$path = trim($path ?? '');
		$path = $this->convert_encoding( $path );//文字コードを揃える
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//バックスラッシュをスラッシュに置き換える。
		$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		$prefix = '';
		if( preg_match( '/^((?:[a-zA-Z0-9]+\\:)?\\/)(\\/.*)$/', $path ?? '', $matched ) ){
			$prefix = $matched[1];
			$path = $matched[2];
		}
		$path = preg_replace( '/\\/+/s', '/', $path ?? '' );//重複するスラッシュを1つにまとめる
		return $prefix.$path;
	}


	/**
	 * パスをOSの標準的な表現に変換する。
	 *
	 * 受け取ったパスを、OSの標準的な表現に変換します。
	 * - スラッシュとバックスラッシュの違いを吸収し、`DIRECTORY_SEPARATOR` に置き換えます。
	 *
	 * @param string $path ローカライズするパス
	 * @return string ローカライズされたパス
	 */
	public function localize_path($path){
		if( is_null($path) ){ return null; }
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//一旦スラッシュに置き換える。
		if( $this->is_unix() ){
			// Windows以外だった場合に、ボリュームラベルを受け取ったら削除する
			$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		}
		$path = preg_replace( '/\\/+/s', '/', $path );//重複するスラッシュを1つにまとめる
		$path = preg_replace( '/\\/|\\\\/s', DIRECTORY_SEPARATOR, $path );
		return $path;
	}

	/**
	 * 受け取ったテキストを、ファイルシステムエンコードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $to_encoding 文字セット(省略時、内部文字セット)
	 * @param string $from_encoding 変換前の文字セット
	 * @return string 文字セット変換後のテキスト
	 */
	public function convert_encoding( $text, $to_encoding = null, $from_encoding = null ){
		$RTN = $text;
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}

		$to_encoding_fin = $to_encoding;
		if( !strlen($to_encoding_fin ?? '') ){
			$to_encoding_fin = mb_internal_encoding();
		}
		if( !strlen($to_encoding_fin ?? '') ){
			$to_encoding_fin = 'UTF-8';
		}

		$from_encoding_fin = (is_string($from_encoding) && strlen($from_encoding) ? $from_encoding : 'UTF-8,SJIS-win,cp932,eucJP-win,SJIS,EUC-JP,JIS,ASCII');

		// ---
		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){
				return $text;
			}
			foreach( $text as $key=>$row ){
				$RTN[$key] = $this->convert_encoding( $row, $to_encoding, $from_encoding );
			}
		}else{
			if( !strlen( $text ?? '' ) ){
				return $text;
			}
			$RTN = mb_convert_encoding( $text ?? '', $to_encoding_fin, $from_encoding_fin );
		}
		return $RTN;
	}

	/**
	 * 受け取ったテキストを、指定の改行コードに変換する。
	 *
	 * @param mixed $text テキスト
	 * @param string $crlf 改行コード名。CR|LF(default)|CRLF
	 * @return string 改行コード変換後のテキスト
	 */
	public function convert_crlf( $text, $crlf = null ){
		if( !strlen($crlf ?? '') ){
			$crlf = 'LF';
		}
		$crlf_code = "\n";
		switch(strtoupper($crlf ?? '')){
			case 'CR':
				$crlf_code = "\r";
				break;
			case 'CRLF':
				$crlf_code = "\r\n";
				break;
			case 'LF':
			default:
				$crlf_code = "\n";
				break;
		}
		$RTN = $text;
		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){
				return $text;
			}
			foreach( $text as $key=>$val ){
				$RTN[$key] = $this->convert_crlf( $val , $crlf );
			}
		}else{
			if( !strlen( $text ?? '' ) ){
				return $text;
			}
			$RTN = preg_replace( '/\r\n|\r|\n/', $crlf_code, $text );
		}
		return $RTN;
	}

}
