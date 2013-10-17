@if ($method->has($ev)) 
    @if (empty($args)) 
        @set($args, NULL)
    @end
    @if (in_array('public', $method['visibility']))
        @if (in_array('static', $method['visibility']))
            $return = \{{$method['class']}}::{{$method['function']}}($document, $args, $this->connection, {{var_export($args ?: $method[0]['args'], true)}}, $this);
        @else
            $return = {{$target}}->{{$method['function']}}($document, $args, $this->connection, {{var_export($args ?: $method[0]['args'], true)}}, $this);
        @end
    @else
        $reflection = new ReflectionMethod("\\{{addslashes($doc['class'])}}", "{{$method['function']}}");
        $return = $reflection->invoke($document, {{$target}}, $args, $this->connection, {{var_export($args ?: $method[0]['args'], true)}}, $this);
    @end
    if ($return === FALSE) {
        throw new \RuntimeException("{{addslashes($doc['class']) . "::" . $method['function']}} returned false");
    }
@end

