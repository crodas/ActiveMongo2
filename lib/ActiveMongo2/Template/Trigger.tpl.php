@if ($method->has($ev)) 
    @if (in_array('public', $method['visibility']))
        $return = {{$target}}->{{$method['function']}}($document, $args, $this->connection, {{var_export($method[0]['args'], true)}}, $this);
    @else
        $reflection = new ReflectionMethod("\\{{addslashes($doc['class'])}}", "{{$method['function']}}");
        $return = $reflection->invoke($document, {{$target}}, $args, $this->connection, {{var_export($method[0]['args'], true)}}, $this);
    @end
    if ($return === FALSE) {
        throw new \RuntimeException("{{addslashes($doc['class']) . "::" . $method['function']}} returned false");
    }
@end
