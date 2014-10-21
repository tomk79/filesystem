<?php
/**
 * test for tomk79\filesystem
 * 
 * $ cd (project dir)
 * $ ./vendor/phpunit/phpunit/phpunit php/tests/filesystemTest
 */
require_once( __DIR__.'/../filesystem.php' );

class filesystemTest extends PHPUnit_Framework_TestCase{

	private $fs;

	public function setup(){
		mb_internal_encoding('UTF-8');
		$conf = new stdClass;
		if( DIRECTORY_SEPARATOR == '\\' ){
			$conf->filesystem_encoding = "Shift_JIS";
		}
		$this->fs = new tomk79\filesystem($conf);
	}

	// ----------------------------------------------------------------------------
	// ユーティリティのテスト

	/**
	 * 絶対パス解決のテスト
	 */
	public function testGetRealpath(){

		$this->assertEquals(
			$this->fs->get_realpath('/'),
			realpath('/')
		);

		$this->assertEquals(
			$this->fs->get_realpath('./mktest/aaa.txt'),
			$this->fs->localize_path(realpath('.').'/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('./mktest/./aaa.txt', __DIR__),
			$this->fs->localize_path(__DIR__.'/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath(__DIR__.'/./mktest/../aaa.txt'),
			$this->fs->localize_path(__DIR__.'/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('C:\\mktest\\aaa.txt'),
			$this->fs->localize_path('C:/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('\\\\mktest\\aaa.txt'),
			$this->fs->localize_path('//mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('../../../mktest/aaa.txt','/aaa/'),
			$this->fs->localize_path('/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('/mktest/','/aaa/'),
			DIRECTORY_SEPARATOR.'mktest'.DIRECTORY_SEPARATOR
		);

	}

	/**
	 * 相対パス解決のテスト
	 */
	public function testGetRelatedpath(){
		return;
		$this->assertEquals(
			$this->fs->get_relatedpath('/reltest/aaa.txt', '/'),
			$this->fs->localize_path( './reltest/aaa.txt' )
		);

		$this->assertEquals(
			$this->fs->get_relatedpath('/reltest/aaa.txt', '/reltest/'),
			$this->fs->localize_path( './aaa.txt' )
		);

		$this->assertEquals(
			$this->fs->get_relatedpath('/reltest/aaa.txt', '/reltest/reltest2/'),
			$this->fs->localize_path( '../aaa.txt' )
		);

		$this->assertEquals(
			$this->fs->get_relatedpath('/reltest/aaa.txt', '/reltest/reltest2/reltest3/'),
			$this->fs->localize_path( '../../aaa.txt' )
		);

		// $this->assertEquals(
		// 	$this->fs->get_relatedpath('./mktest/./aaa.txt', __DIR__),
		// 	$this->fs->localize_path(__DIR__.'/mktest/aaa.txt')
		// );

		// $this->assertEquals(
		// 	$this->fs->get_relatedpath(__DIR__.'/./mktest/../aaa.txt'),
		// 	$this->fs->localize_path(__DIR__.'/aaa.txt')
		// );

		// $this->assertEquals(
		// 	$this->fs->get_relatedpath('C:\\mktest\\aaa.txt'),
		// 	$this->fs->localize_path('C:/mktest/aaa.txt')
		// );

		// $this->assertEquals(
		// 	$this->fs->get_relatedpath('\\\\mktest\\aaa.txt'),
		// 	$this->fs->localize_path('//mktest/aaa.txt')
		// );

		// $this->assertEquals(
		// 	$this->fs->get_relatedpath('../../../mktest/aaa.txt','/aaa/'),
		// 	$this->fs->localize_path('/mktest/aaa.txt')
		// );

	}

	/**
	 * ファイルリスト取得のテスト
	 */
	public function testLs(){
		$ls = $this->fs->ls( __DIR__ );

		$this->assertEquals( count($ls), 3 );

		// ↓返却値は strnatcmp順(ABC順) でソートされているはず。
		$i = 0;
		$this->assertEquals( $ls[$i++], 'data' );
		$this->assertEquals( $ls[$i++], 'filesystemTest.php' );
		$this->assertEquals( $ls[$i++], 'mktest' );

		$this->assertFalse( array_search('.', $ls) );
		$this->assertFalse( array_search('..', $ls) );
	}

	/**
	 * 文字列変換のテスト
	 */
	public function testTextConvert(){

		// 改行コード
		$this->assertEquals(
			$this->fs->convert_crlf('aa'."\r".'bb'."\r\n"),
			'aa'."\n".'bb'."\n"
		);

		$this->assertEquals(
			$this->fs->convert_crlf('aa'."\r".'bb'."\r\n", 'LF'),
			'aa'."\n".'bb'."\n"
		);

		$this->assertEquals(
			$this->fs->convert_crlf('aa'."\r".'bb'."\r\n", 'lf'),
			'aa'."\n".'bb'."\n"
		);

		$this->assertEquals(
			$this->fs->convert_crlf('aa'."\r".'bb'."\r\n", 'crlf'),
			'aa'."\r\n".'bb'."\r\n"
		);

		// 文字コード
		$sample = mb_convert_encoding( '日本語の文字列(UTF-8)', 'UTF-8', mb_internal_encoding() );

		$this->assertNotEquals(
			mb_convert_encoding($sample, 'SJIS-win', mb_internal_encoding()),
			$sample
		);

		$this->assertEquals(
			$this->fs->convert_encoding(
				$this->fs->convert_encoding($sample, 'SJIS-win'),
				'UTF-8'
			),
			$sample
		);

	}


	/**
	 * ファイルの更新日時を比較するテスト
	 */
	public function testIsNewerAThanB(){
		touch(__DIR__.'/data/timestamp/file_new.txt');
		$this->assertTrue( $this->fs->is_newer_a_than_b(
			__DIR__.'/data/timestamp/file_new.txt',
			__DIR__.'/data/timestamp/file_old.txt'
		) );
	}


	// ----------------------------------------------------------------------------
	// ディレクトリ操作のテスト

	/**
	 * dataProvidor: ディレクトリ一覧
	 */
	public function directoryProvider(){
		return array(
			array( __DIR__.'/mktest/testDirectory/'      , 'testDirR/' ) ,
			array( __DIR__.'/mktest/テストディレクトリ/' , 'testDirR/') ,
			array( __DIR__.'/mktest\\testDirectoryWin\\' , 'testDirR/') ,
		);
	}

	/**
	 * ディレクトリ作成のテスト(単階層)
	 * @depends testGetRealpath
	 * @depends testLs
	 * @dataProvider directoryProvider
	 */
	public function testMkDir( $path, $sub_dir ){
		// ディレクトリを作成
		clearstatcache();
		$this->assertTrue( $this->fs->mkdir( $path ) );

		// ディレクトリの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_dir($path) );
	}

	/**
	 * ディレクトリ削除のテスト(単階層)
	 * @depends testGetRealpath
	 * @depends testLs
	 * @depends testMkDir
	 * @dataProvider directoryProvider
	 */
	public function testRmDir( $path, $sub_dir ){

		// ディレクトリの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_dir($path) );

		// ディレクトリの削除
		clearstatcache();
		$this->assertTrue( $this->fs->rmdir( $path ) );

		// ディレクトリの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_dir($path) );

	}

	/**
	 * ディレクトリ作成のテスト(多階層)
	 * @depends testGetRealpath
	 * @depends testLs
	 * @dataProvider directoryProvider
	 */
	public function testMkDirR( $path, $sub_dir ){
		// ディレクトリを作成(これは失敗する)
		clearstatcache();
		$this->assertFalse( $this->fs->mkdir( $path.$sub_dir ) );

		// ディレクトリの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_dir($path.$sub_dir) );

		// ディレクトリを作成(Rをつけると成功する)
		clearstatcache();
		$this->assertTrue( $this->fs->mkdir_r( $path.$sub_dir ) );

		// ディレクトリの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_dir($path) );
		$this->assertTrue( $this->fs->is_dir($path.$sub_dir) );
	}

	/**
	 * ディレクトリ削除のテスト(多階層)
	 * @depends testGetRealpath
	 * @depends testLs
	 * @depends testMkDirR
	 * @dataProvider directoryProvider
	 */
	public function testRmDirR( $path, $sub_dir ){

		// ディレクトリの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_dir($path.$sub_dir) );

		// ディレクトリの削除(中が空じゃないので失敗する)
		clearstatcache();
		$this->assertFalse( $this->fs->rmdir( $path ) );

		// ディレクトリの削除(再帰的に削除するので成功する)
		clearstatcache();
		$this->assertTrue( $this->fs->rmdir_r( $path ) );

		// ディレクトリの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_dir($path) );
	}


	// ----------------------------------------------------------------------------
	// ファイル操作のテスト

	/**
	 * dataProvidor: ファイルデータ
	 */
	public function fileProvider(){
		return array(
			array( __DIR__.'/mktest/testfile.txt', 'test test' ) ,
		);
	}

	/**
	 * ファイル作成のテスト
	 * @depends testGetRealpath
	 * @depends testLs
	 * @dataProvider fileProvider
	 */
	public function testSaveFile( $path, $content ){

		// ファイルパスのパーミッション確認
		clearstatcache();
		$this->assertTrue( $this->fs->is_writable( $path ) );

		// ファイルを作成
		clearstatcache();
		$this->assertTrue( $this->fs->save_file( $path, $content ) );

		// ファイルの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_file( $path ) );

		// ファイルを読み込んで、内容を確認
		clearstatcache();
		$this->assertEquals( $this->fs->read_file( $path ), $content );

	}

	/**
	 * ファイル削除のテスト
	 * @depends testGetRealpath
	 * @depends testLs
	 * @depends testSaveFile
	 * @dataProvider fileProvider
	 */
	public function testRmFile( $path, $content ){

		// ファイルパスのパーミッション確認
		clearstatcache();
		$this->assertTrue( $this->fs->is_writable( $path ) );

		// ファイルを削除
		clearstatcache();
		$this->assertTrue( $this->fs->rm( $path ) );

		// ファイルの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_file( $path ) );
	}

	/**
	 * ファイル移動のテスト
	 * @depends testGetRealpath
	 * @depends testLs
	 * @depends testSaveFile
	 * @depends testRmFile
	 * @dataProvider renameProvider
	 */
	public function testRename( $path_1, $path_2 ){

		// ファイルを作成
		clearstatcache();
		$this->assertTrue( $this->fs->save_file( $path_1, 'rename_test...' ) );

		// ファイルの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_file( $path_1 ) );

		// ファイルを移動
		clearstatcache();
		$this->assertTrue( $this->fs->rename( $path_1, $path_2 ) );

		// ファイルの存在確認(1はなくて、2はあるべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_file( $path_1 ) );
		$this->assertTrue( $this->fs->is_file( $path_2 ) );

		// ファイルを削除
		clearstatcache();
		$this->assertTrue( $this->fs->rm( $path_2 ) );

		// ファイルの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_file( $path_2 ) );

	}
	public function renameProvider(){
		return array(
			array( __DIR__.'/mktest/test_1.txt', __DIR__.'/mktest/test_2.txt' ) ,
			array( __DIR__.'/mktest/テスト1.txt', __DIR__.'/mktest/テスト2.txt' ) ,
		);
	}

	/**
	 * 深いファイル移動のテスト
	 * @depends testGetRealpath
	 * @depends testLs
	 * @depends testSaveFile
	 * @depends testRmFile
	 * @depends testRmDirR
	 * @depends testRename
	 */
	public function testRenameR(){
		$this->fs->mkdir( __DIR__.'/mktest/testdir1/' );
		$this->fs->save_file( __DIR__.'/mktest/testdir1/test1.txt', 'rename_test...' );

		// ファイルとディレクトリの存在確認(存在するべき)
		clearstatcache();
		$this->assertTrue( $this->fs->is_dir( __DIR__.'/mktest/testdir1/' ) );
		$this->assertTrue( $this->fs->is_file( __DIR__.'/mktest/testdir1/test1.txt' ) );

		// ディレクトリを移動
		clearstatcache();
		$this->assertTrue( $this->fs->rename_f( __DIR__.'/mktest/testdir1/', __DIR__.'/mktest/testdir2/deep3/testdir1/' ) );

		// ファイルの存在確認(1はなくて、2はあるべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_dir( __DIR__.'/mktest/testdir1/' ) );
		$this->assertFalse( $this->fs->is_file( __DIR__.'/mktest/testdir1/test1.txt' ) );
		clearstatcache();
		$this->assertTrue( $this->fs->is_file( __DIR__.'/mktest/testdir2/deep3/testdir1/test1.txt' ) );

		// ファイルを削除
		clearstatcache();
		$this->assertTrue( $this->fs->rm( __DIR__.'/mktest/testdir2/' ) );

		// ファイルの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_file( __DIR__.'/mktest/testdir2/' ) );

	}


	// ----------------------------------------------------------------------------
	// CSVファイル操作のテスト

	/**
	 * CSVファイル読み込みのテスト
	 * @depends testGetRealpath
	 * @depends testLs
	 */
	public function testReadCsv(){

		// 読み込むテスト
		$csvPath = __DIR__.'/data/test01.csv';
		clearstatcache();
		$this->assertTrue( $this->fs->is_file( $csvPath ) );

		$csv01 = $this->fs->read_csv( $csvPath );
		$this->assertEquals( gettype($csv01), gettype(array()) );
		$this->assertCount( 11, $csv01 );
		$this->assertCount(  4, $csv01[0] );
		$this->assertCount(  3, $csv01[1] );
		$this->assertEquals( $csv01[1][1], 'te,st' );
		$this->assertEquals( $csv01[2][0], 'te"st' );

		$this->assertEquals( $csv01[7][1], '日本語1-2' );
		$this->assertEquals( $csv01[8][1], '日本語2-2' );

		$this->assertEquals(
			$this->fs->convert_crlf($csv01[10][0]),
			$this->fs->convert_crlf('このセルは、改行を含みます。'."\n\n".'ここまでで1つのセルです。')
		);

	}


	/**
	 * CSV形式のデータを作成するテスト
	 */
	public function testMkCsv(){

		$this->assertEquals(
			$this->fs->mk_csv(
				array(
					array('a','b','c'),
					array('d','e','f'),
				)
			),
			'"a","b","c"'."\n".'"d","e","f"'."\n"
		);

		$this->assertEquals(
			$this->fs->mk_csv(
				array(
					array('a','b,c'),
					array('d','e"e','f'),
				)
			),
			'"a","b,c"'."\n".'"d","e""e","f"'."\n"
		);

		$this->assertEquals(
			$this->fs->mk_csv(
				array(
					array('a','日本語を含むCSV形式'),
					array('d','日本語を含むCSV形式','f'),
				)
			),
			'"a","日本語を含むCSV形式"'."\n".'"d","日本語を含むCSV形式","f"'."\n"
		);

	}


}
