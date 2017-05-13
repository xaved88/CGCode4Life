<?php

function elog($var)
{
    error_log(var_export($var, true));
}