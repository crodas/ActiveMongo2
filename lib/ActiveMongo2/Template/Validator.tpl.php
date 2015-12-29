@set($val, $validator->getVariables())
@set($var, $val['var']);

@foreach ($val['functions'] as $name => $body)
function {{$name}} ({{$var}})
{
    $is_scalar = is_scalar({{$var}});
    {{$body->toCode($var)}}
    return {{$body->result}};
}

@end

