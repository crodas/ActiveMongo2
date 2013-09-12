@if (empty($var)) 
    @set($var, 'doc')
@end
@foreach($validators as $name => $callback)
    @if ($prop->has($name))
        /* {{$prop['property']}} - {{$name}} {{ '{{{' }} */
        if (empty($this->loaded['{{$files[$name]}}'])) {
            require_once __DIR__ . '{{$files[$name]}}';
            $this->loaded['{{$files[$name]}}'] = true;
        }
        if (!empty(${{$var}}['{{$propname}}']) && !{{$callback}}(${{$var}}['{{$propname}}'], {{var_export(($prop[0]['args']) ?: [],  true)}}, $this->connection, $this)) {
            throw new \RuntimeException("Validation failed for {{$name}}");
        }
        /* }}} */

    @end
@end
