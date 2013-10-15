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

        $args = {{var_export(($prop[0]['args']) ?: [],  true)}};
        @if (!empty($prop[0]['args']))
            @foreach($prop[0]['args'] as $i => $val)
                @if ($val[0] == '$')
                    $args[{{$i}}] = ${{$var}}["{{substr($val,1)}}"];
                @end
            @end
        @end

        if (!empty(${{$var}}['{{$propname}}']) && !{{$callback}}(${{$var}}['{{$propname}}'], $args, $this->connection, $this)) {
            throw new \RuntimeException("Validation failed for {{$name}}");
        }
        /* }}} */

    @end
@end
