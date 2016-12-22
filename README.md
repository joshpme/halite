# Better PHP Halite.io module

Cleaner to use halite.io PHP library

Currently only written for windows

## Installation

1. Put halite.exe in root folder
2. Run whole directory under a web server

## Usage 

Initialize with:
```PHP
namespace Halite;
require("halite/loader.php");
$map = new Map();
```

Send moves and get next frame with

```PHP
$map->update();
```

### Debug mode

Call Map constructor with boolean true

```PHP
$map = new Map(true);
```

Run bot directly from webserver (eg. http://localhost/halite/preview/) to run single player emulation


### Preview

Visiting http://localhost/halite/preview/ will present a preview window that runs that game via exec.
