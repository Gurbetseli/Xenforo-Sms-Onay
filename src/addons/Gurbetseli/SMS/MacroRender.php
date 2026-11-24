<?php

namespace Gurbetseli\SMS;

class MacroRender
{
    public static function preRender(\XF\Template\Templater $templater, &$type, &$template, &$name, array &$arguments, array &$globalVars)
    {
        if ($arguments['group']->group_id == 'gurbetseli_sms')
        {
            $template = 'gurbetseli_sms_option_macros';
        }
    }
}