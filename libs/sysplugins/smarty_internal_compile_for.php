<?php
/**
 * Smarty Internal Plugin Compile For
 * Compiles the {for} {forelse} {/for} tags
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     Uwe Tews
 */

/**
 * Smarty Internal Plugin Compile For Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_For extends Smarty_Internal_CompileBase
{
    /**
     * Compiles code for the {for} tag
     * Smarty 3 does implement two different syntax's:
     * - {for $var in $array}
     * For looping over arrays or iterators
     * - {for $x=0; $x<$y; $x++}
     * For general loops
     * The parser is generating different sets of attribute by which this compiler can
     * determine which syntax is used.
     *
     * @param  array  $args      array with attributes from parser
     * @param  object $compiler  compiler object
     * @param  array  $parameter array with compilation parameter
     *
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        $compiler->loopNesting ++;
        if ($parameter == 0) {
            $this->required_attributes = array('start', 'to');
            $this->optional_attributes = array('max', 'step');
        } else {
            $this->required_attributes = array('start', 'ifexp', 'var', 'step');
            $this->optional_attributes = array();
        }
        $this->mapCache = array();
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);

        $output = "<?php\n";
        if ($parameter == 1) {
            foreach ($_attr[ 'start' ] as $_statement) {
                if (is_array($_statement[ 'var' ])) {
                    $var = $_statement[ 'var' ][ 'var' ];
                    $index = $_statement[ 'var' ][ 'smarty_internal_index' ];
                } else {
                    $var = $_statement[ 'var' ];
                    $index = '';
                }
                $output .= "\$_cse_vars[$var] = new Smarty_Variable(null, \$_smarty_tpl->isRenderingCache);\n";
                $output .= "\$_cse_vars[$var]->value{$index} = {$_statement['value']};\n";
            }
            if (is_array($_attr[ 'var' ])) {
                $var = $_attr[ 'var' ][ 'var' ];
                $index = $_attr[ 'var' ][ 'smarty_internal_index' ];
            } else {
                $var = $_attr[ 'var' ];
                $index = '';
            }
            $output .= "if ($_attr[ifexp]) {\nfor (\$_foo=true;$_attr[ifexp]; \$_cse_vars[$var]->value{$index}$_attr[step]) {\n";
        } else {
            $_statement = $_attr[ 'start' ];
            if (is_array($_statement[ 'var' ])) {
                $var = $_statement[ 'var' ][ 'var' ];
                $index = $_statement[ 'var' ][ 'smarty_internal_index' ];
            } else {
                $var = $_statement[ 'var' ];
                $index = '';
            }
            $output .= "\$_cse_vars[$var] = new Smarty_Variable(null, \$_smarty_tpl->isRenderingCache);";
            if (isset($_attr[ 'step' ])) {
                $output .= "\$_cse_vars[$var]->step = $_attr[step];";
            } else {
                $output .= "\$_cse_vars[$var]->step = 1;";
            }
            if (isset($_attr[ 'max' ])) {
                $output .= "\$_cse_vars[$var]->total = (int) min(ceil((\$_cse_vars[$var]->step > 0 ? $_attr[to]+1 - ($_statement[value]) : $_statement[value]-($_attr[to])+1)/abs(\$_cse_vars[$var]->step)),$_attr[max]);\n";
            } else {
                $output .= "\$_cse_vars[$var]->total = (int) ceil((\$_cse_vars[$var]->step > 0 ? $_attr[to]+1 - ($_statement[value]) : $_statement[value]-($_attr[to])+1)/abs(\$_cse_vars[$var]->step));\n";
            }
            $output .= "if (\$_cse_vars[$var]->total > 0) {\n";
            $output .= "for (\$_cse_vars[$var]->value{$index} = $_statement[value], \$_cse_vars[$var]->iteration = 1;\$_cse_vars[$var]->iteration <= \$_cse_vars[$var]->total;\$_cse_vars[$var]->value{$index} += \$_cse_vars[$var]->step, \$_cse_vars[$var]->iteration++) {\n";
            $output .= "\$_cse_vars[$var]->first = \$_cse_vars[$var]->iteration == 1;";
            $output .= "\$_cse_vars[$var]->last = \$_cse_vars[$var]->iteration == \$_cse_vars[$var]->total;";
        }
        $output .= "?>";

        $this->openTag($compiler, 'for', array('for', $compiler->nocache));
        // maybe nocache because of nocache variables
        $compiler->nocache = $compiler->nocache | $compiler->tag_nocache;
        // return compiled code
        return $output;
    }
}

/**
 * Smarty Internal Plugin Compile Forelse Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Forelse extends Smarty_Internal_CompileBase
{
    /**
     * Compiles code for the {forelse} tag
     *
     * @param  array  $args      array with attributes from parser
     * @param  object $compiler  compiler object
     * @param  array  $parameter array with compilation parameter
     *
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);

        list($openTag, $nocache) = $this->closeTag($compiler, array('for'));
        $this->openTag($compiler, 'forelse', array('forelse', $nocache));

        return "<?php }} else { ?>";
    }
}

/**
 * Smarty Internal Plugin Compile Forclose Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Forclose extends Smarty_Internal_CompileBase
{
    /**
     * Compiles code for the {/for} tag
     *
     * @param  array  $args      array with attributes from parser
     * @param  object $compiler  compiler object
     * @param  array  $parameter array with compilation parameter
     *
     * @return string compiled code
     */
    public function compile($args, $compiler, $parameter)
    {
        $compiler->loopNesting --;
        // check and get attributes
        $_attr = $this->getAttributes($compiler, $args);
        // must endblock be nocache?
        if ($compiler->nocache) {
            $compiler->tag_nocache = true;
        }

        list($openTag, $compiler->nocache) = $this->closeTag($compiler, array('for', 'forelse'));

        $output = "<?php }\n";
        if ($openTag != 'forelse') {
            $output .= "}\n";
        }
        $output .= "?>\n";
        return $output;
    }
}
