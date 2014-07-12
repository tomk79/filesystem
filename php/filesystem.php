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
	 * ファイルオープンリソースのリスト
	 */
	private $file = array();

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
		if( strlen( $conf->file_default_permission ) ){
			$this->default_permission['file'] = octdec( $conf->file_default_permission );
		}
		if( strlen( $conf->dir_default_permission ) ){
			$this->default_permission['file'] = octdec( $conf->dir_default_permission );
		}
		if( strlen( $conf->filesystem_encoding ) ){
			$this->filesystem_encoding = octdec( $conf->filesystem_encoding );
		}
	}

	/**
	 * 書き込み/上書きしてよいアイテムか検証する。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool 書き込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_writable( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		if( @file_exists( $path ) && !@is_writable( $path ) ){
			return false;
		}
		return true;
	}//is_writable()

	/**
	 * 読み込んでよいアイテムか検証する。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool 読み込み可能な場合 `true`、不可能な場合に `false` を返します。
	 */
	public function is_readable( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		if( !@is_readable( $path ) ){
			return false;
		}
		return true;
	}//is_readable()

	/**
	 * ファイルが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ファイルが存在する場合 `true`、存在しない場合、またはディレクトリが存在する場合に `false` を返します。
	 */
	public function is_file( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		return @is_file( $path );
	}//is_file()

	/**
	 * ディレクトリが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ディレクトリが存在する場合 `true`、存在しない場合、またはファイルが存在する場合に `false` を返します。
	 */
	public function is_dir( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		return @is_dir( $path );
	}//is_dir()

	/**
	 * ファイルまたはディレクトリが存在するかどうか調べる。
	 * 
	 * @param string $path 検証対象のパス
	 * @return bool ファイルまたはディレクトリが存在する場合 `true`、存在しない場合に `false` を返します。
	 */
	public function file_exists( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		return @file_exists( $path );
	}//file_exists()

	/**
	 * ディレクトリを作成する。
	 * 
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir( $dirpath , $perm = null ){
		if( strlen( $this->filesystem_encoding ) ){
			$dirpath = @$this->convert_encoding( $dirpath , $this->filesystem_encoding );
		}

		if( @is_dir( $dirpath ) ){
			#	既にディレクトリがあったら、作成を試みない。
			$this->chmod( $dirpath , $perm );
			return true;
		}
		$result = @mkdir( $dirpath );
		$this->chmod( $dirpath , $perm );
		clearstatcache();
		return	$result;
	}//mkdir()

	/**
	 * ディレクトリを作成する(上層ディレクトリも全て作成)
	 * 
	 * @param string $dirpath 作成するディレクトリのパス
	 * @param int $perm 作成するディレクトリに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function mkdir_r( $dirpath , $perm = null ){
		if( strlen( $this->filesystem_encoding ) ){
			$dirpath = @$this->convert_encoding( $dirpath );
		}

		if( @is_dir( $dirpath ) ){ return true; }
		if( @is_file( $dirpath ) ){ return false; }
		$patharray = explode( '/' , $this->get_realpath( $dirpath ) );
		$targetpath = '';
		foreach( $patharray as $Line ){
			if( !strlen( $Line ) || $Line == '.' || $Line == '..' ){ continue; }
			$targetpath = $targetpath.'/'.$Line;
			if( !@is_dir( $targetpath ) ){
				$targetpath = @$this->convert_encoding( $targetpath );
				$this->mkdir( $targetpath , $perm );
			}
		}
		return true;
	}//mkdir_r()

	/**
	 * ファイルを保存する。
	 * 
	 * このメソッドは、`$filepath` にデータを保存します。
	 * もともと保存されていた内容は破棄され、新しいデータで上書きします。
	 * 
	 * ただし、`fopen()` したリソースは、1回の処理の間保持されるので、
	 * 1回の処理で同じファイルに対して2回以上コールされた場合は、
	 * 追記される点に注意してください。
	 * 1回の処理の間に何度も上書きする必要がある場合は、
	 * 明示的に `$dbh->fclose($filepath);` をコールし、一旦ファイルを閉じてください。
	 * 
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function save_file( $filepath , $content , $perm = null ){

		$filepath = $this->get_realpath($filepath);

		if( strlen( $this->filesystem_encoding ) ){
			$filepath = @$this->convert_encoding( $filepath );
		}

		if( !$this->is_writable( $filepath ) )	{ return false; }
		if( @is_dir( $filepath ) ){ return false; }
		if( @is_file( $filepath ) && !@is_writable( $filepath ) ){ return false; }
		if( !is_array( @$this->file[$filepath] ) ){
			$this->fopen( $filepath , 'w' );
		}elseif( $this->file[$filepath]['mode'] != 'w' ){
			$this->fclose( $filepath );
			$this->fopen( $filepath , 'w' );
		}

		if( !strlen( $content ) ){
			#	空白のファイルで上書きしたい場合
			if( @is_file( $filepath ) ){
				@unlink( $filepath );
			}
			@touch( $filepath );
			$this->chmod( $filepath , $perm );
			clearstatcache();
			return @is_file( $filepath );
		}

		$res = $this->file[$filepath]['res'];
		if( !is_resource( $res ) ){ return false; }
		fwrite( $res , $content );
		$this->chmod( $filepath , $perm );
		clearstatcache();
		return @is_file( $filepath );
	}//save_file()

	/**
	 * ファイルを上書き保存して閉じる。
	 * 
	 * @param string $filepath 保存先ファイルのパス
	 * @param string $content 保存する内容
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function file_overwrite( $filepath , $content , $perm = null ){
		if( $this->is_file_open( $filepath ) ){
			#	既に開いているファイルだったら、一旦閉じる。
			$this->fclose( $filepath );
		}

		if( strlen( $this->filesystem_encoding ) ){
			$filepath = @$this->convert_encoding( $filepath );
		}

		#	ファイルを上書き保存
		$result = $this->save_file( $filepath , $content , $perm );

		#	ファイルを閉じる
		$this->fclose( $filepath );
		return	$result;
	}//file_overwrite()

	/**
	 * ファイルの中身を1行ずつ配列にいれて取得する。
	 * 
	 * @param string $path ファイルのパス
	 * @return array ファイル `$path` の内容を1行1要素で格納する配列
	 */
	public function file_get_lines( $path ){

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return	@file( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、
			#	それを取得しようと試みます。
			#	しかし、この使用方法は推奨されません。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリにロードします。
			#	( 2007/01/05 TomK )
			if( !ini_get( 'allow_url_fopen' ) ){
				#	PHP設定値 allow_url_fopen が無効な場合は、
				#	file() によるウェブアクセスができないため。
				$this->px->error()->error_log( 'php.ini value "allow_url_fopen" is FALSE. So, disable to get Web contents ['.$path.'] on $dbh->file_get_lines();' );
				return false;
			}
			return @file( $path );
		}
		return false;
	}//file_get_lines()

	/**
	 * ファイルの中身を文字列型にして取得する。
	 * 
	 * @param string $path ファイルのパス
	 * @return string ファイル `$path` の内容
	 */
	public function file_get_contents( $path ){

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( @is_file( $path ) ){
			if( !$this->is_readable( $path ) ){ return false; }
			return file_get_contents( $path );
		}elseif( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $path ) ){
			#	対象がウェブコンテンツの場合、それを取得しようと試みます。
			#	ただし、ウェブコンテンツをこのメソッドからダウンロードする場合は、
			#	注意が必要です。
			#	対象が、とてもサイズの大きなファイルだったとしても、
			#	このメソッドはそれを検証しません。
			#	また、そのように巨大なファイルの場合でも、
			#	ディスクではなく、メモリに直接ロードします。
			return	$this->get_http_content( $path );
		}
		return false;
	}//file_get_contents()

	/**
	 * HTTP通信からコンテンツを取得する。
	 * 
	 * 対象が、とてもサイズの大きなファイルだったとしても、
	 * このメソッドはそれを検証しないことに注意してください。
	 * また、そのように巨大なファイルの場合でも、
	 * ディスクではなく、メモリに直接ロードします。
	 * 
	 * @param string $url ファイルのURL
	 * @param string $saveTo 取得したファイルの保存先パス(省略可)
	 * @return string|bool `$saveTo` が省略された場合、取得したコンテンツを返します。`$saveTo` が指定された場合、保存成功時に `true`、失敗時に `false` を返します。
	 */
	public function get_http_content( $url , $saveTo = null ){

		if( !ini_get('allow_url_fopen') ){
			#	PHP設定値 allow_url_fopen が無効な場合は、
			#	file() によるウェブアクセスができないためエラー。
			return false;
		}
		if( preg_match( '/^(?:http:\/\/|https:\/\/)/' , $url ) ){
			if( !is_null( $saveTo ) ){
				#	取得したウェブコンテンツを
				#	ディスクに保存する場合

				if( @is_file( $saveTo ) && !@is_writable( $saveTo ) ){
					#	保存先ファイルが既に存在するのに、書き込めなかったらfalse;
					return false;

				}elseif( !@is_file( $saveTo ) && @is_dir( dirname( $saveTo ) ) && !@is_writable( dirname( $saveTo ) ) ){
					#	保存先ファイルが存在しなくて、
					#	親ディレクトリがあるのに、書き込めなかったらfalse;
					return false;

				}

				if( !@is_dir( dirname( $saveTo ) ) ){
					#	親ディレクトリがなかったら、作ってみる。
					if( !$this->mkdir_r( dirname( $saveTo ) ) ){
						#	失敗したらfalse;
						return false;
					}
				}

				#	重たいファイルを考慮して、
				#	1行ずつディスクに保存していく。
				$res = $this->fopen( $url , 'r' , false );
				while( $LINE = @fgets( $res ) ){
					if( !strlen( $LINE ) ){ break; }
					$this->save_file( $saveTo , $LINE );
				}
				$this->fclose( $url );
				return true;
			}

			#	取得したウェブコンテンツのバイナリを
			#	メモリにロードする場合。
			return file_get_contents( $url );
		}
		return false;
	}//get_http_content()

	/**
	 * ファイルの更新日時を比較する。
	 * 
	 * @param string $path_a 比較対象A
	 * @param string $path_b 比較対象B
	 * @return bool|null 
	 * `$path_a` の方が新しかった場合に `true`、
	 * `$path_b` の方が新しかった場合に `false`、
	 * 同時だった場合に `null` を返します。
	 */
	public function is_newer_a_than_b( $path_a , $path_b ){
		if( strlen( $this->filesystem_encoding ) ){
			$path_a = @$this->convert_encoding( $path_a );
			$path_b = @$this->convert_encoding( $path_b );
		}

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
		if( strlen( $this->filesystem_encoding ) ){
			$original = @$this->convert_encoding( $original );
			$newname  = @$this->convert_encoding( $newname  );
		}

		if( !@file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		return @rename( $original , $newname );
	}//rename()

	/**
	 * ファイル名/ディレクトリ名の変更を完全に実行する。
	 *
	 * 移動先の親ディレクトリが存在しない場合にも、親ディレクトリを作成して移動するよう試みます。
	 *
	 * @param string $original 現在のファイルまたはディレクトリ名
	 * @param string $newname 変更後のファイルまたはディレクトリ名
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function rename_complete( $original , $newname ){
		if( strlen( $this->filesystem_encoding ) ){
			$original = @$this->convert_encoding( $original );
			$newname  = @$this->convert_encoding( $newname  );
		}

		if( !@file_exists( $original ) ){ return false; }
		if( !$this->is_writable( $original ) ){ return false; }
		$dirname = dirname( $newname );
		if( !@is_dir( $dirname ) ){
			if( !$this->mkdir_r( $dirname ) ){
				return false;
			}
		}
		return @rename( $original , $newname );
	}//rename_complete()

	/**
	 * 絶対パスを得る。
	 * 
	 * パス情報を受け取り、スラッシュから始まるサーバー内部絶対パスに変換して返します。
	 * 
	 * このメソッドは、PHPの `realpath()` と異なり、存在しないパスも絶対パスに変換します。
	 * ただし、ルート直下のディレクトリまでは一致している必要があり、そうでない場合は、`false` を返します。
	 * 
	 * @param string $path 対象のパス
	 * @param string $itemname 再帰的に処理する場合に使用(初回コール時は使用しません)
	 * @return string 絶対パス
	 */
	public function get_realpath( $path , $itemname = null ){
		$path = preg_replace( '/\\\\/si' , '/' , $path );
		$path = preg_replace( '/^\/+/si' , '/' , $path );//先頭のスラッシュを1つにする。
		$itemname = preg_replace( '/\\\\/si' , '/' , $itemname );
		$itemname = preg_replace( '/^\/'.'*'.'/' , '/' , $itemname );//先頭のスラッシュを1つにする。

		if( $itemname == '/' ){ $itemname = ''; }//スラッシュだけが残ったら、ゼロバイトの文字にする。
		if( t::realpath( $path ) == '/' ){
			$rtn = $path.$itemname;
			$rtn = preg_replace( '/\/+/si' , '/' , $rtn );//先頭のスラッシュを1つにする。
			return $rtn;
		}

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( @file_exists( $path ) && strlen(t::realpath( $path )) ){
			return t::realpath( $path ).$itemname;
		}

		if( basename( $path ) == '.' ){
			#	カレントディレクトリを含むパスへの対応
			return $this->get_realpath( dirname( $path ) , $itemname );
		}
		if( basename( $path ) == '..' ){
			$count = 0;
			while( basename( $path ) == '..' && strlen( dirname( $path ) ) && dirname( $path ) != '/' ){
				$count ++;
				$path = dirname( $path );
			}
			for( $i = 0; $i < $count; $i++ ){
				$path = dirname( $path );
			}
			#	ペアレントディレクトリを含むパスへの対応
			return $this->get_realpath( $path , $itemname );
		}
		return $this->get_realpath( dirname( $path ) , basename( $path ).$itemname );
	}

	/**
	 * パス情報を得る。
	 * 
	 * @param string $path 対象のパス
	 * @return array パス情報
	 */
	public function pathinfo( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}
		$pathinfo = pathinfo( $path );
		$pathinfo['filename'] = $this->trim_extension( $pathinfo['basename'] );
		return $pathinfo;
	}

	/**
	 * パス情報から、ファイル名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 抜き出されたファイル名
	 */
	public function get_basename( $path ){
		return pathinfo( $path , PATHINFO_BASENAME );
	}

	/**
	 * パス情報から、拡張子を除いたファイル名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 拡張子が除かれたパス
	 */
	public function trim_extension( $path ){
		$pathinfo = pathinfo( $path );
		$RTN = preg_replace( '/\.'.preg_quote( $pathinfo['extension'] , '/' ).'$/' , '' , $path );
		return $RTN;
	}

	/**
	 * ファイル名を含むパス情報から、ファイルが格納されているディレクトリ名を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 親ディレクトリのパス
	 */
	public function get_dirpath( $path ){
		return pathinfo( $path , PATHINFO_DIRNAME );
	}

	/**
	 * パス情報から、拡張子を取得する。
	 * 
	 * @param string $path 対象のパス
	 * @return string 拡張子
	 */
	public function get_extension( $path ){
		return pathinfo( $path , PATHINFO_EXTENSION );
	}


	/**
	 * CSVファイルを読み込む。
	 * 
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv( $path , $options = array() ){
		#	$options['charset'] は、保存されているCSVファイルの文字エンコードです。
		#	省略時は SJIS-win から、内部エンコーディングに変換します。

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		$path = t::realpath( $path );
		if( !@is_file( $path ) ){
			#	ファイルがなければfalseを返す
			return false;
		}

		if( !strlen( @$options['delimiter'] ) )    { $options['delimiter'] = ','; }
		if( !strlen( @$options['enclosure'] ) )    { $options['enclosure'] = '"'; }
		if( !strlen( @$options['size'] ) )         { $options['size'] = 10000; }
		if( !strlen( @$options['charset'] ) )      { $options['charset'] = 'SJIS-win'; }

		$RTN = array();
		if( !$this->fopen($path,'r') ){ return false; }
		$filelink = $this->get_file_resource($path);
		if( !is_resource( $filelink ) || !is_null( @$this->file[$path]['contents'] ) ){
			return $this->file[$path]['contents'];
		}
		while( $SMMEMO = fgetcsv( $filelink , intval( $options['size'] ) , $options['delimiter'] , $options['enclosure'] ) ){
			$SMMEMO = @mb_convert_encoding( $SMMEMO , mb_internal_encoding() , $options['charset'].',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
			array_push( $RTN , $SMMEMO );
		}
		$this->fclose($path);
		return $RTN;
	}//read_csv()

	/**
	 * UTF-8のCSVファイルを読み込む
	 * 
	 * @param string $path 対象のCSVファイルのパス
	 * @param array $options オプション
	 * @return array|bool 読み込みに成功した場合、行列を格納した配列、失敗した場合には `false` を返します。
	 */
	public function read_csv_utf8( $path , $options = array() ){
		#	読み込み時にUTF-8の解釈が優先される。
		if( !gettype($options) ){
			$options = array();
		}
		$options['charset'] = 'UTF-8';
		return $this->read_csv( $path , $options );
	}//read_csv_utf8()

	/**
	 * 配列をCSV形式に変換する
	 * 
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv( $array , $options = array() ){
		#	$options['charset'] は、出力されるCSV形式の文字エンコードを指定します。
		#	省略時は Shift_JIS に変換して返します。
		if( !is_array( $array ) ){ $array = array(); }

		if( !strlen( $options['charset'] ) ){ $options['charset'] = 'SJIS-win'; }
		$RTN = '';
		foreach( $array as $Line ){
			if( is_null( $Line ) ){ continue; }
			if( !is_array( $Line ) ){ $Line = array(); }
			foreach( $Line as $cell ){
				$cell = @mb_convert_encoding( $cell , $options['charset'] , mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP' );
				if( preg_match( '/"/' , $cell ) ){
					$cell = preg_replace( '/"/' , '""' , $cell);
				}
				if( strlen( $cell ) ){
					$cell = '"'.$cell.'"';
				}
				$RTN .= $cell.',';
			}
			$RTN = preg_replace( '/,$/' , '' , $RTN );
			$RTN .= "\r\n";
		}
		return	$RTN;
	}//mk_csv()

	/**
	 * 配列をUTF8-エンコードのCSV形式に変換する。
	 * 
	 * @param array $array 2次元配列
	 * @param array $options オプション
	 * @return string 生成されたCSV形式のテキスト
	 */
	public function mk_csv_utf8( $array , $options = array() ){
		if( !is_array($options) ){
			$options = array();
		}
		$options['charset'] = 'UTF-8';
		return $this->mk_csv( $array , $options );
	}//mk_csv_utf8()

	/**
	 * ファイルを複製する。
	 * 
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy( $from , $to , $perm = null ){
		if( strlen( $this->filesystem_encoding ) ){
			$from = @$this->convert_encoding( $from );
			$to   = @$this->convert_encoding( $to   );
		}

		if( !@is_file( $from ) ){
			return false;
		}
		if( !$this->is_readable( $from ) ){
			return false;
		}

		if( @is_file( $to ) ){
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
	 * ディレクトリを複製する(下層ディレクトリも全てコピー)
	 * 
	 * @param string $from コピー元ファイルのパス
	 * @param string $to コピー先のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function copy_r( $from , $to , $perm = null ){
		if( strlen( $this->filesystem_encoding ) ){
			$from = @$this->convert_encoding( $from );
			$to   = @$this->convert_encoding( $to   );
		}

		$result = true;

		if( @is_file( $from ) ){
			if( $this->mkdir_r( dirname( $to ) ) ){
				if( !$this->copy( $from , $to , $perm ) ){
					$result = false;
				}
			}else{
				$result = false;
			}
		}elseif( @is_dir( $from ) ){
			if( !@is_dir( $to ) ){
				if( !$this->mkdir_r( $to ) ){
					$result = false;
				}
			}
			$itemlist = $this->ls( $from );
			foreach( $itemlist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				if( @is_dir( $from.'/'.$Line ) ){
					if( @is_file( $to.'/'.$Line ) ){
						continue;
					}elseif( !@is_dir( $to.'/'.$Line ) ){
						if( !$this->mkdir_r( $to.'/'.$Line ) ){
							$result = false;
						}
					}
					if( !$this->copy_r( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}elseif( @is_file( $from.'/'.$Line ) ){
					if( !$this->copy_r( $from.'/'.$Line , $to.'/'.$Line , $perm ) ){
						$result = false;
					}
					continue;
				}
			}
		}

		return $result;
	}//copy_r()

	/**
	 * ファイルを開き、ファイルリソースをセットする。
	 * 
	 * @param string $filepath ファイルのパス
	 * @param string $mode モード
	 * @param bool $flock ファイルをロックするフラグ
	 * @return resource|bool 成功したらファイルリソースを、失敗したら `false` を返します。
	 */
	private function &fopen( $filepath , $mode = 'r' , $flock = true ){
		$filepath_fsenc = $filepath;
		if( strlen( $this->filesystem_encoding ) ){
			//PxFW 0.6.4 追加
			$filepath_fsenc = @$this->convert_encoding( $filepath_fsenc );
		}

		$filepath = $this->get_realpath( $filepath );

		#	すでに開かれていたら
		if( is_resource( @$this->file[$filepath]['res'] ) ){
			if( $this->file[$filepath]['mode'] != $mode ){
				#	$modeが前回のアクセスと違っていたら、
				#	前回の接続を一旦closeして、開きなおす。
				$this->fclose( $filepath );
			}else{
				#	前回と$modeが一緒であれば、既に開いているので、
				#	ここで終了。
				return	$this->file[$filepath]['res'];
			}
		}

		#	対象がディレクトリだったら開けません。
		if( @is_dir( $filepath_fsenc ) ){
			return false;
		}

		#	ファイルが存在するかどうか確認
		if( @is_file( $filepath_fsenc ) ){
			$filepath = t::realpath( $filepath );
			#	対象のパーミッションをチェック
			switch( strtolower($mode) ){
				case 'r':
					if( !$this->is_readable( $filepath ) ){ return false; }
					break;
				case 'w':
				case 'a':
				case 'x':
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
				case 'r+':
				case 'w+':
				case 'a+':
				case 'x+':
					if( !$this->is_readable( $filepath ) ){ return false; }
					if( !$this->is_writable( $filepath ) ){ return false; }
					break;
			}
		}

		if( is_array( @$this->file[$filepath] ) ){ $this->fclose( $filepath ); }

		for( $i = 0; $i < 5; $i++ ){
			$res = @fopen( $filepath_fsenc , $mode );
			if( $res ){ break; }		#	openに成功したらループを抜ける
			sleep(1);
		}
		if( !is_resource( $res ) ){ return false; }	#	5回挑戦して読み込みが成功しなかった場合、falseを返す
		if( $flock ){ flock( $res , LOCK_EX ); }
		if( @is_file( $filepath_fsenc ) ){
			$filepath = t::realpath( $filepath );
		}
		$this->file[$filepath]['filepath'] = $filepath;
		$this->file[$filepath]['res'] = $res;
		$this->file[$filepath]['mode'] = $mode;
		$this->file[$filepath]['flock'] = $flock;
		return	$res;
	}//fopen()

	/**
	 * ファイルのリソースを取得する。
	 * 
	 * @param string $filepath ファイルのパス
	 * @return resource ファイルリソース
	 */
	private function &get_file_resource( $filepath ){
		$filepath = $this->get_realpath($filepath);
		return	$this->file[$filepath]['res'];
	}//get_file_resource()

	/**
	 * パーミッションを変更する。
	 * 
	 * @param string $filepath 対象のパス
	 * @param int $perm 保存するファイルに与えるパーミッション
	 * @return bool 成功時に `true`、失敗時に `false` を返します。
	 */
	public function chmod( $filepath , $perm = null ){
		if( strlen( $this->filesystem_encoding ) ){
			$filepath = @$this->convert_encoding( $filepath );
		}

		if( is_null( $perm ) ){
			if( @is_dir( $filepath ) ){
				$perm = $this->default_permission['dir'];
			}else{
				$perm = $this->default_permission['file'];
			}
		}
		if( is_null( $perm ) ){
			$perm = 0775;	//	コンフィグに設定モレがあった場合
		}
		return	@chmod( $filepath , $perm );
	}//chmod()

	/**
	 * パーミッション情報を調べ、3桁の数字で返す。
	 * 
	 * @param string $path 対象のパス
	 * @return int|bool 成功時に 3桁の数字、失敗時に `false` を返します。
	 */
	public function get_permission( $path ){
		if( strlen( $this->filesystem_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @$this->convert_encoding( $path );
		}
		$path = @realpath( $path );
		if( !@file_exists( $path ) ){ return false; }
		$perm = rtrim( sprintf( "%o\n" , fileperms( $path ) ) );
		$start = strlen( $perm ) - 3;
		return	substr( $perm , $start , 3 );
	}//get_permission()


	/**
	 * ディレクトリにあるファイル名のリストを配列で返す。
	 * 
	 * @param string $path 対象ディレクトリのパス
	 * @return array|bool 成功時にファイルまたはディレクトリ名の一覧を格納した配列、失敗時に `false` を返します。
	 */
	public function ls($path){
		if( strlen( $this->filesystem_encoding ) ){
			//PxFW 0.6.4 追加
			$path = @$this->convert_encoding( $path );
		}
		$path = @realpath($path);
		if( $path === false ){ return false; }
		if( !@file_exists( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }

		$RTN = array();
		$dr = @opendir($path);
		while( ( $ent = readdir( $dr ) ) !== false ){
			#	CurrentDirとParentDirは含めない
			if( $ent == '.' || $ent == '..' ){ continue; }
			array_push( $RTN , $ent );
		}
		closedir($dr);
		if( strlen( $this->filesystem_encoding ) ){
			//PxFW 0.6.4 追加
			$RTN = @$this->convert_encoding( $RTN );
		}
		usort($RTN, "strnatcmp");
		return	$RTN;
	}//ls()

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

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#	ファイルまたはシンボリックリンクの場合の処理
			$result = @unlink( $path );
			return	$result;

		}elseif( @is_dir( $path ) ){
			#	ディレクトリの処理
			$flist = $this->ls( $path );
			foreach ( $flist as $Line ){
				if( $Line == '.' || $Line == '..' ){ continue; }
				$this->rm( $path.'/'.$Line );
			}
			$result = @rmdir( $path );
			return	$result;

		}

		return false;
	}//rm()

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

		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( !$this->is_writable( $path ) ){
			return false;
		}
		$path = @realpath( $path );
		if( $path === false ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){
			#   ファイルまたはシンボリックリンクの場合の処理
			#   ディレクトリ以外は削除できません。
			return false;

		}elseif( @is_dir( $path ) ){
			#   ディレクトリの処理
			#   rmdir() は再帰的削除を行いません。
			#   再帰的に削除したい場合は、代わりに rm() を使用します。
			$result = @rmdir( $path );
			return	$result;
		}

		return false;
	}//rmdir()

	/**
	 * ディレクトリの内部を比較し、$comparisonに含まれない要素を$targetから削除する。
	 *
	 * @param string $target クリーニング対象のディレクトリパス
	 * @param string $comparison 比較するディレクトリのパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	public function compare_and_cleanup( $target , $comparison ){
		if( is_null( $comparison ) || is_null( $target ) ){ return false; }

		if( strlen( $this->filesystem_encoding ) ){
			$target = @$this->convert_encoding( $target );
			$comparison = @$this->convert_encoding( $comparison );
		}

		if( !@file_exists( $comparison ) && @file_exists( $target ) ){
			$this->rm( $target );
			return true;
		}

		if( @is_dir( $target ) ){
			$flist = $this->ls( $target );
		}else{
			return true;
		}

		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			$this->compare_and_cleanup( $target.'/'.$Line , $comparison.'/'.$Line );
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
		if( strlen( $this->filesystem_encoding ) ){
			$path = @$this->convert_encoding( $path );
		}

		if( !$this->is_writable( $path ) ){ return false; }
		if( !@is_dir( $path ) ){ return false; }
		if( @is_file( $path ) || @is_link( $path ) ){ return false; }
		$path = @realpath( $path );
		if( $path === false ){ return false; }

		#--------------------------------------
		#	次の階層を処理するかどうかのスイッチ
		$switch_donext = false;
		if( is_null( $options['depth'] ) ){
			#	深さの指定がなければ掘る
			$switch_donext = true;
		}elseif( !is_int( $options['depth'] ) ){
			#	指定がnullでも数値でもなければ掘らない
			$switch_donext = false;
		}elseif( $options['depth'] <= 0 ){
			#	指定がゼロ以下なら、今回の処理をして終了
			$switch_donext = false;
		}elseif( $options['depth'] > 0 ){
			#	指定が正の数(ゼロは含まない)なら、掘る
			$options['depth'] --;
			$switch_donext = true;
		}else{
			return false;
		}
		#	/ 次の階層を処理するかどうかのスイッチ
		#--------------------------------------

		$flist = $this->ls( $path );
		if( !count( $flist ) ){
			#	開いたディレクトリの中身が
			#	"." と ".." のみだった場合
			#	削除して終了
			$result = @rmdir( $path );
			return	$result;
		}
		$alive = false;
		foreach ( $flist as $Line ){
			if( $Line == '.' || $Line == '..' ){ continue; }
			if( @is_link( $path.'/'.$Line ) ){
				#	シンボリックリンクはシカトする。
			}elseif( @is_dir( $path.'/'.$Line ) ){
				if( $switch_donext ){
					#	さらに掘れと指令があれば、掘る。
					$this->remove_empty_dir( $path.'/'.$Line , $options );
				}
			}
			if( @file_exists( $path.'/'.$Line ) ){
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

		if( strlen( $this->filesystem_encoding ) ){
			//PxFW 0.6.4 追加
			$dir_a = @$this->convert_encoding( $dir_a );
			$dir_b = @$this->convert_encoding( $dir_b );
		}

		if( ( @is_file( $dir_a ) && !@is_file( $dir_b ) ) || ( !@is_file( $dir_a ) && @is_file( $dir_b ) ) ){
			return false;
		}
		if( ( ( @is_dir( $dir_a ) && !@is_dir( $dir_b ) ) || ( !@is_dir( $dir_a ) && @is_dir( $dir_b ) ) ) && $options['compare_emptydir'] ){
			return false;
		}

		if( @is_file( $dir_a ) && @is_file( $dir_b ) ){
			#--------------------------------------
			#	両方ファイルだったら
			if( $options['compare_filecontent'] ){
				#	ファイルの内容も比較する設定の場合、
				#	それぞれファイルを開いて同じかどうかを比較
				$filecontent_a = $this->file_get_contents( $dir_a );
				$filecontent_b = $this->file_get_contents( $dir_b );
				if( $filecontent_a !== $filecontent_b ){
					return false;
				}
			}
			return true;
		}

		if( @is_dir( $dir_a ) || @is_dir( $dir_b ) ){
			#--------------------------------------
			#	両方ディレクトリだったら
			$contlist_a = $this->ls( $dir_a );
			$contlist_b = $this->ls( $dir_b );

			if( $options['compare_emptydir'] && $contlist_a !== $contlist_b ){
				#	空っぽのディレクトリも厳密に評価する設定で、
				#	ディレクトリ内の要素配列の内容が異なれば、false。
				return false;
			}

			$done = array();
			foreach( $contlist_a as $Line ){
				#	Aをチェック
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $options ) ){
					return false;
				}
				$done[$Line] = true;
			}

			foreach( $contlist_b as $Line ){
				#	Aに含まれなかったBをチェック
				if( $done[$Line] ){ continue; }
				if( $Line == '..' || $Line == '.' ){ continue; }
				if( !$this->compare_dir( $dir_a.'/'.$Line , $dir_b.'/'.$Line , $options ) ){
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
	 * 開いているファイルを閉じる。
	 * 
	 * @param string $filepath 閉じるファイルパス
	 * @return bool 成功時 `true`、失敗時 `false` を返します。
	 */
	private function fclose( $filepath ){
		$filepath = $this->get_realpath( $filepath );
		if( !$this->is_file_open( $filepath ) ){
			#	ファイルを開いていない状態だったらスキップ
			return false;
		}

		if( $this->file[$filepath]['flock'] ){
			flock( $this->file[$filepath]['res'] , LOCK_UN );
		}
		fclose( $this->file[$filepath]['res'] );
		unset( $this->file[$filepath] );
		return true;
	}

	/**
	 * ファイルを開いている状態か確認する。
	 * 
	 * @param string $filepath 調査対象のファイルパス
	 * @return bool すでに開いている場合 `true`、開いていない場合に `false` を返します。
	 */
	private function is_file_open( $filepath ){
		$filepath = $this->get_realpath( $filepath );
		if( !@array_key_exists( $filepath , $this->file ) ){ return false; }
		if( !@is_array( $this->file[$filepath] ) ){ return false; }
		return true;
	}

	/**
	 * パスを正規化する。
	 * 
	 * 受け取ったパスを、OSの標準的な表現に正規化します。
	 * - スラッシュとバックスラッシュの違いを吸収し、`DIRECTORY_SEPARATOR` に置き換えます。
	 * 
	 * @param string $path 正規化するパス
	 * @return string 正規化されたパス
	 */
	private function normalize_path($path){
		$path = $this->convert_encoding( $path );//文字コードを揃える
		$path = preg_replace( '/\\/|\\\\/s', '/', $path );//一旦スラッシュに置き換える。
		if( $this->is_windows() ){
			$path = preg_replace( '/^[A-Z]\\:\\//s', '/', $path );//Windowsのボリュームラベルを削除
		}
		$path = preg_replace( '/\\/+/s', '/', $path );//重複するスラッシュを1つにまとめる
		$path = preg_replace( '/\\/|\\\\/s', DIRECTORY_SEPARATOR, $path );
		return $path;
	}

	/**
	 * 受け取ったテキストを、指定の文字セットに変換する。
	 * 
	 * @param mixed $text テキスト
	 * @return string 文字セット変換後のテキスト
	 */
	private function convert_encoding( $text ){
		if( !is_callable( 'mb_internal_encoding' ) ){
			return $text;
		}
		if( !strlen( $this->filesystem_encoding ) ){
			return $text;
		}

		$encode = $this->filesystem_encoding;
		if( !strlen( $encode ) ){
			$encode = mb_internal_encoding();
		}
		if( !strlen( $encodefrom ) ){
			$encodefrom = mb_internal_encoding().',UTF-8,SJIS-win,eucJP-win,SJIS,EUC-JP,JIS,ASCII';
		}

		if( is_array( $text ) ){
			$RTN = array();
			if( !count( $text ) ){ return $text; }
			$TEXT_KEYS = array_keys( $text );
			foreach( $TEXT_KEYS as $Line ){
				$KEY = @mb_convert_encoding( $Line , $encode , $encodefrom );
				if( is_array( $text[$Line] ) ){
					$RTN[$KEY] = $this->convert_encoding( $text[$Line] );
				}else{
					$RTN[$KEY] = @mb_convert_encoding( $text[$Line] , $encode , $encodefrom );
				}
			}
		}else{
			if( !strlen( $text ) ){ return $text; }
			$RTN = @mb_convert_encoding( $text );
		}
		return $RTN;
	}

}