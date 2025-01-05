# JAN13 Barcode Generator
## 概要
このプログラムは、PNG、SVG、EPSでJAN13のバーコード画像を生成します。あなたの制作物にJAN13バーコードを挿入できます。
（Online版Excelにバーコードを表示したくて作りました）

## インストール
PHP >= 8.1、php-yaml、php-imagick、Imagickが必要です。imagickのインストールの詳細については、
[Config sample](config/config_sample.yml) を参照してください。
なお、一部のLinuxディストリビューションに含まれるimagickではデフォルトの `policy.xml` でEPSが無効になっていますが、
このプログラムはimagickを使用せずにEPSを直接出力するため、EPSを有効化する必要はありません。

1. このリポジトリをcloneします。
```
$ git clone "https://github.com/demicchi/barcode-generator"
```
2. composer を使用して依存ファイルをインストールします。
```
$ composer install
```
3. `config_sample.yml` の名前を `config.yml` に変更し、あなたのインストール環境に応じて値を変更します。
4. 適切なパーミッションを設定します。特に、 `log/` は注意してください。
5. SELinuxを有効にしている場合は適切なラベルを設定します。少なくとも、 `httpd_sys_rw_content_t` を `log(/.*)` に適用する必要があります。
```
# semanage fcontext -d -t httpd_sys_rw_content_t "[インストール ディレクトリ]/log(/.*)?"
# restorecon -R [インストール ディレクトリ]
```

## 使用方法
### Excel
任意のセルに `=IMAGE([URL])` と入力するとバーコード画像を挿入できます。PNG形式を指定する必要があります。`IMAGE()` は、デスクトップ版では
比較的新しいバージョンのExcelで使用できます。Online版のExcelでもサポートされています。

例:
```
=IMAGE("https://yourdomain/index.php?code=012345678901&format=png")
```

### Illustratorや他のベクター式のソフトウェア
SVGまたはEPS形式を使用すると良いでしょう。

## オプション

| parameter    | 説明                                                                            | 例             |
|--------------|-------------------------------------------------------------------------------|---------------|
| code         | JAN13コード。コード長が13文字の場合は最後の1桁(チェックディジット)は無視されます。                                | 2054209100069 |
| height       | 高さ (px)。                                                                      | 100           |
| width_factor | 各バーの幅 (px)。                                                                   | 2             |
| numbered     | バーの下に OCR-B の番号を挿入するには 1 に設定し、それ以外の場合は 0 に設定します。                              | 1             |
| margin       | 生成された画像内の 4 つの端の空白スペース (px)。                                                  | 2             |
| format       | 画像形式。`png`、`svg`、`eps`                                                        | png           |
| background   | 生成された画像の背景色。形式は `[R]、[G]、[B]、[a]` です。`R`、`G`、`B` の各値は 0 ～ 255、`a` は 0 ～ 1 です。 | 255,255,255,1 |
| foreground   | 生成された画像の前景色。フォーマットは背景と同じです。                                                   | 0,0,0,1       |
| download     | 「名前を付けて保存」ダイアログを表示するには 1 に設定し、それ以外の場合は 0 に設定します。                              | 1             |

### URL の例
`https://yourdomain/index.php?code=2054209100069&height=100&width_factor=2&numbered=1&margin=2&format=png&background=255,255,255,1&foreground=0,0,0,1&download=0`

## 追加の考慮事項
ユーザからのアクセスをindex.phpのみに限定してください。

nginxの例:
```
root /opt/barcode-generator;
index index.php;
location ~ ^/(?:(?:index)\.php|$) {
    fastcgi_split_path_info ^(.+\.php)(.*)$;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param SCRIPT_NAME $fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
    fastcgi_pass php-fpm; # Upstream here
    fastcgi_intercept_errors on;
    fastcgi_request_buffering off;
}
location ~ .* {
    return 404;
}
```

また、 `/log` 配下のファイルにlogrotateを適用してください。

## ライセンス
このプログラムは [PHP Barcode Generator](https://github.com/picqer/php-barcode-generator)
を一部改造しているので、ライセンスはLGPLv3です。

## おまけ
先に英語のreadmeを書いてから日本語に翻訳したのでなんだかちょっとたどたどしい感じになってしまいました。でもこういう技術文書は日本語から英語に翻訳するほうが大変だし……。