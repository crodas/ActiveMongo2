<?php
/*
  +---------------------------------------------------------------------------------+
  | Copyright (c) 2014 ActiveMongo                                                  |
  +---------------------------------------------------------------------------------+
  | Redistribution and use in source and binary forms, with or without              |
  | modification, are permitted provided that the following conditions are met:     |
  | 1. Redistributions of source code must retain the above copyright               |
  |    notice, this list of conditions and the following disclaimer.                |
  |                                                                                 |
  | 2. Redistributions in binary form must reproduce the above copyright            |
  |    notice, this list of conditions and the following disclaimer in the          |
  |    documentation and/or other materials provided with the distribution.         |
  |                                                                                 |
  | 3. All advertising materials mentioning features or use of this software        |
  |    must display the following acknowledgement:                                  |
  |    This product includes software developed by César D. Rodas.                  |
  |                                                                                 |
  | 4. Neither the name of the César D. Rodas nor the                               |
  |    names of its contributors may be used to endorse or promote products         |
  |    derived from this software without specific prior written permission.        |
  |                                                                                 |
  | THIS SOFTWARE IS PROVIDED BY CÉSAR D. RODAS ''AS IS'' AND ANY                   |
  | EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED       |
  | WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE          |
  | DISCLAIMED. IN NO EVENT SHALL CÉSAR D. RODAS BE LIABLE FOR ANY                  |
  | DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES      |
  | (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;    |
  | LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND     |
  | ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT      |
  | (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS   |
  | SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE                     |
  +---------------------------------------------------------------------------------+
  | Authors: César Rodas <crodas@php.net>                                           |
  +---------------------------------------------------------------------------------+
*/
namespace ActiveMongo2\Filter;

function _mkdir($dir)
{
    if (!is_dir($dir)) {
        if (!@mkdir($dir)) {
            throw new \RuntimeException("Cannot create directory {$dir}");
        }
    }
}

/**
 *  @Validate(FileUpload)
 *  @Type File
 */
function __filter_upload(&$upload, $args, $conn, $params, $mapper)
{
    $dir = $mapper->getRelativePath($params['Path']);
    _mkdir($dir);

    if (!is_array($upload)) {
        if (empty($_FILES[$upload])) {
            throw new \RuntimeException("Undefined variable \$_FILE['$upload']");
        }
        $upload = $_FILES[$upload];
    }

    if (!empty($upload['stored'])) {
        return true;
    }

    if (empty($upload['tmp_name']) || empty($upload['name'])) {
        throw new \RuntimeException("Invalid \$_FILES object");
    }

    $part = explode(".", $upload['name']);
    $ext  = "";
    if (!empty($part)) {
        $ext = "." . end($part);
    }
    $value = sha1_file($upload['tmp_name']) . $ext;
    $path  = "";
    foreach (str_split(substr($value, 0, 4), 2) as $d) {
        $path .= "/{$d}";
        _mkdir($dir . $path);
    }

    $realpath = $dir . "/{$path}/" . substr($value, 4);
    if (!move_uploaded_file($upload['tmp_name'], $realpath)) {
        if (!copy($upload['tmp_name'], $realpath) || !unlink($upload['tmp_name'])) {
            throw new \RuntimeException("Cannot move to the final destination");
        }
    }

    $upload = array(
        'realpath' => realpath($realpath),
        'relpath' => $path . $value,
        'stored'  => true,
    );

    return true;
}
