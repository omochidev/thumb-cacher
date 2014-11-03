thumb-cacher
============

Thumb Cacher Class for generating thumbs.

Instalation
------------

It's easy like a walk in the park, just copy the class in your project and import it like `required_once "ThumbCacher.php"`.
Now, call these two methods in your configuration (before outputting any image):

`ThumbCacher::setPhysicalFolder('/var/www/mysite.com/files');`
`ThumbCacher::setVirtualFolder('http://mysite.com/files');`

In the folder named "files", you must have a folder named "originals" for the big images.
The script will create in the same level another folder named "resized" for the thumbs.

Example:

`ThumbCacher::image('image.jpg', array(
	'width' => 200,
	'height' => 200,
));`

Will result in a string like this: `http://mysite.com/files/200x200_image.jpg`
Then you can put it in a `<img src="" >` without a problem.

CakePHP import
---------------

Copy the PHP file to the `app/Vendor` folder in your project and import it in the controller you'd like to use.
Or you can just import it in the bootstrap.php it'll be fine.

`App::import('Vendor', 'ThumbCacher');`


Contributions
--------------

Contributions are always welcomed! So, if you want to develop in another languague, it'll be awesome.
Just clone the repo, submit a pull and I will revise it. =)
