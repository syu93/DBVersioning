        ____  ____ _    __               _             _            
       / __ \/ __ ) |  / /__  __________(_)___  ____  (_)___  ____ _
      / / / / __  | | / / _ \/ ___/ ___/ / __ \/ __ \/ / __ \/ __ `/
     / /_/ / /_/ /| |/ /  __/ /  (__  ) / /_/ / / / / / / / / /_/ / 
    /_____/_____/ |___/\___/_/  /____/_/\____/_/ /_/_/_/ /_/\__, /  
                                                           /____/   version 0.0.1
# DBVersioning - PHP-based database versioning

## Install:
Clone DBVersioning from github:
```bash
    $ git clone https://github.com/syu93/DBVersioning.git
```
`Note:` To install it globaly, copy the __*dbversioning*__ and __*dbversioning.php*__ scripts in your bin folder: **/usr/local/bin**.

Then it done. Juste run:
```bash
    $ php dbversioning.php [commands] [options]
```
Or if you have installed it globaly:
```bash
    $ dbversioning [commands] [options]
```

## Usage:
We assume you're using one of the two install methods. So we omit the tool name before the command.Refer above for more explanation.
```bash
    $ command [options] [arguments]
```
        $ command -H for help.
For exemple:

```bash
    $ dbversioning init -H
```
Will print:

    Usage:
        init [options]
    Options:
      -d 		Database name.
      -h 		Server host name. Default: localhost.
      -u 		Database user. Default: root.
      -p 		Database password.
      -t 		[optional] The table to export.
      -T 		[optional] The list of table to export
      --path 	[optional] The dbv folder path. Default: dbv
    Help:
        The init command initialize DBVersioning by reading and saving database records in the 'dbv/data/records/table_name.json'.

## Options:
        -v          Display the application version.

        -h, --help 	Display this help message.

### The following commands are currently supported:
        init 		Initialize DBVersioning by reading and saving records.
  
        update 	    Update saved records
  
        diff 		Create the revision file to update the database