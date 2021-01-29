# Infracamp kicker

**This is an internal project.**

This Project is part of the **kickstart-flavor-* ** projects

## .kick.yml Reference


```yaml
version: 1
from: "from/docker-image"

config_file:
  template: "config.php.dist"
  target: "config.php"

# Install packages via apt-get
packages: [package1, package2]

# Set Environment Variables
env:
  - SOME_ENV=Some value 
  - PATH="/some/path:$PATH"
  - "SOME_ENV=${SOME_ENV:-defaultValue}"      # Define default environment

command:
    command_name1:
      - "script to exec (as user)"
        
    command_shell: |
      if [ -f /some/file ]; then
         echo "file found"
      fi;
      
     
```

## Config file writer

kicker can replace values from environment in your config files. Just define a `template` and a
`target` in your `.kick.yml`:

```yaml
config_file:
  template: "test/test.in.txt"
  target: "/tmp/test.out.txt"
```
The action `kick write_config_files` will take the template-file, replace placeholders
and write it to `target` on each start of the container.

Placeholders are:

```
%NAME_OF_REQUIRED_ENV%
```

Optional with default value:

```
%ENV_NAME?default_value%
```

Attention: The placeholder will be replaced by the shell-escaped value of the
environment. But **it won't add quotes around the value**!

To correctly handle values, you should quote every placeholder:

```php
define ("SOME_CONSTANT", "%ENV_NAME?default_value%");
```

## Config Template

Server Configuration can be stored in `.kicker/conf/<path>`. If this directory
is present, all contents will be parsed and copied to the dedicated subfolder
in the containers root file system.


