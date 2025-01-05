# JAN13 Barcode Generator
## Overview
This program generates JAN13 barcode images in PNG, SVG and EPS. You can insert JAN13 barcodes in your works.

## Installation
PHP >= 8.1, php-yaml, php-imagick and Imagick are required. See [Config sample](config/config_sample.yml) 
for more information about your imagick installation.
You may need to allow EPS in `policy.xml` of your imagick installation because imagick on some Linux distributions 
disables EPS by default.

1. Clone this repository.
```
$ git clone "https://github.com/demicchi/barcode-generator"
```
2. Install dependencies using composer:
```
$ composer install
```
3. Rename `config_sample.yml` to `config.yml` and change the values according to your environment.
4. Set appropriate permissions, especially to `log/`.
5. Set appropriate labels if you deploy SELinux. At least `httpd_sys_rw_content_t` must be applied to `log(/.*)`.
```
# semanage fcontext -d -t httpd_sys_rw_content_t "[Your installation directory]/log(/.*)?"
# restorecon -R [Your installation directory]
```

## Usage
### Excel
Insert a barcode image by typing `=IMAGE([URL])` in any cell. PNG format is supported by Excel. Note that `IMAGE()` is 
supported by relatively newer Excel of desktop version and Excel on the web.

Example:
```
=IMAGE("https://yourdomain/index.php?code=012345678901&format=png")
```

### Illustrator or other vector software
You can use SVG or EPS format.

## Options

| parameter    | description                                                                                                                           | example       |
|--------------|---------------------------------------------------------------------------------------------------------------------------------------|---------------|
| code         | JAN13 code. The last one digit (check digit) is ignored if the code length is 13.                                                     | 2054209100069 |
| height       | Height in px.                                                                                                                         | 100           |
| width_factor | Width of each bar in px.                                                                                                              | 2             |
| numbered     | Set to 1 to insert numbers of OCR-B under the bars, otherwise 0.                                                                      | 1             |
| margin       | Blank space of four edges inside the generated image in px.                                                                           | 2             |
| format       | Image format. `png` , `svg` , `eps`                                                                                                   | png           |
| background   | Background color of the generated image. The format is `[R],[G],[B],[a]` where each value of `R` , `G` , `B` is 0-255 and `a` is 0-1. | 255,255,255,1 |
| foreground   | Foreground color of the generated image. The format is the same as background.                                                        | 0,0,0,1       |
| download     | Set to 1 to show "Download As" dialog, otherwise 0.                                                                                   | 1             |

### URL Example
`https://yourdomain/index.php?code=2054209100069&height=100&width_factor=2&numbered=1&margin=2&format=png&background=255,255,255,1&foreground=0,0,0,1&download=0`

## Additional considerations
Limit user accesses only to index.php.

nginx example:
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

Also, apply logrotate to files under `/log` .


## License
This program is based on [PHP Barcode Generator](https://github.com/picqer/php-barcode-generator) 
and is therefore licensed under LGPLv3.



