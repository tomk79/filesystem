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
		$this->fs = new tomk79\filesystem();
	}

	// ----------------------------------------------------------------------------
	// ユーティリティテスト

	/**
	 * 絶対パス解決のテスト
	 */
	public function testGetRealpath(){
		$this->assertEquals(
			$this->fs->get_realpath('./mktest/aaa.txt'),
			$this->fs->normalize_path(realpath('.').'/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('./mktest/./aaa.txt', __DIR__),
			$this->fs->normalize_path(__DIR__.'/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath(__DIR__.'/./mktest/../aaa.txt'),
			$this->fs->normalize_path(__DIR__.'/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('C:\\mktest\\aaa.txt'),
			$this->fs->normalize_path('C:/mktest/aaa.txt')
		);

		$this->assertEquals(
			$this->fs->get_realpath('\\\\mktest\\aaa.txt'),
			$this->fs->normalize_path('//mktest/aaa.txt')
		);

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
	}

	/**
	 * ファイル削除のテスト
	 * @depends testGetRealpath
	 * @depends testSaveFile
	 * @dataProvider fileProvider
	 */
	public function testRmFile( $path, $content ){

		// ファイルパスのパーミッション確認
		clearstatcache();
		$this->assertTrue( $this->fs->is_writable( $path ) );

		// ファイルを作成
		clearstatcache();
		$this->assertTrue( $this->fs->rm( $path ) );

		// ファイルの存在確認(存在しないべき)
		clearstatcache();
		$this->assertFalse( $this->fs->is_file( $path ) );
	}


	// ----------------------------------------------------------------------------
	// CSVファイル操作のテスト

	/**
	 * dataProvidor: CSVデータ
	 */
	public function csvProvider(){
		return array(
			array( __DIR__.'/data/test01.csv' ) ,
		);
	}

}
