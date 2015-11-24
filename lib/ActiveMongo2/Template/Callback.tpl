@if (!$self->isEmbeddable())
    if (empty(self::$loaded[{{@$self->getPath()}}])) {
        @if ($self->isClass())
            if (!{{$self->getAnnotation()->getObject()->getType()}}_exists({{@$self->getClass()}}, false)) {
        @elif ($self->isMethod())
            if (!{{$self->getAnnotation()->getObject()->getClass()->getObject()->getType()}}_exists({{@$self->getClass()}}, false)) {
        @else
            if (!function_exists({{@$self->getFunction()}})) {
        @end
            require {{@$self->getPath()}};
        }
        self::$loaded[{{@$self->getPath()}}] = true;
    }
@end

$args = empty($args) ? [] : $args;

@if ($self->isEmbeddable())
    {{ $self->toEmbedCode($var) }}
@elif ($self->isMethod())
    @if ($self->isPublic())
        @if ($self->isStatic())
            $return = \{{$self->getClass()}}::{{$self->getMethod()}}(
        @elif ($prop->getClass() == $self->getClass())
            $return = $document->{{$self->getMethod()}}(
        @else
            // Improve me (should construct once and reuse it)
            // {{ get_class($self) . " _ " . get_class($self->getAnnotation()) }}
            $return = (new \{{$self->getClass()}})->{{$self->getMethod()}}(
        @end
            {{$var}}, // document variable 
            $args,  // external arguments (defined at run time)
            $this->connection, // connection
            {{@$args}}, // annotation arguments
            $this, // mapper instance
            {{@$prop->getClass()}}
        );
    @else
        $reflection = new \ReflectionMethod({{@"\\". $self->getClass()}}, {{@$self->getMethod()}});
        $reflection->setAccessible(true);
        $return = $reflection->invoke(
            {{$var}}, // document variable 
            $args,  // external arguments (defined at run time)
            $this->connection, // connection
            {{@$args}}, // annotation arguments
            $this, // mapper instance
            {{@$prop->getClass()}}
        );
    @end
@else
    $return = \{{$self->getFunction()}}(
        {{$var}}, // document variable 
        $args,  // external arguments (defined at run time)
        $this->connection, // connection
        {{@$args}}, // annotation arguments
        $this, // mapper instance
        {{@$prop->getClass()}}
    );
@end
