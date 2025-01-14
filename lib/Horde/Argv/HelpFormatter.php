<?php
/**
 * Copyright 2010-2017 Horde LLC (http://www.horde.org/)
 *
 * This package is ported from Python's Optik (http://optik.sourceforge.net/).
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Mike Naberezny <mike@maintainable.com>
 * @license  http://www.horde.org/licenses/bsd BSD
 * @category Horde
 * @package  Argv
 */

/**
 * Provides HelpFormatter, used by Horde_Argv_Parser to generate formatted
 * help text.
 *
 * Abstract base class for formatting option help.  Horde_Argv_Parser
 * instances should use one of the HelpFormatter subclasses for
 * formatting help; by default IndentedHelpFormatter is used.
 *
 * Instance attributes:
 *    parser : Horde_Argv_Parser
 *      the controlling Horde_Argv_Parser instance
 *    _color : Horde_Cli_Color @since Horde_Argv 2.1.0
 *      a color formatter
 *    indent_increment : int
 *      the number of columns to indent per nesting level
 *    max_help_position : int
 *      the maximum starting column for option help text
 *    help_position : int
 *      the calculated starting column for option help text;
 *      initially the same as the maximum
 *    width : int
 *      total number of columns for output (automatically detected by default)
 *    level : int
 *      current indentation level
 *    current_indent : int
 *      current indentation level (in columns)
 *    help_width : int
 *      number of columns available for option help text (calculated)
 *    default_tag : str
 *      text to replace with each option's default value, "%default"
 *      by default.  Set to false value to disable default value expansion.
 *    option_strings : { Option : str }
 *      maps Option instances to the snippet of help text explaining
 *      the syntax of that option, e.g. "-h, --help" or
 *      "-fFILE, --file=FILE"
 *    _short_opt_fmt : str
 *      format string controlling how short options with values are
 *      printed in help text.  Must be either "%s%s" ("-fFILE") or
 *      "%s %s" ("-f FILE"), because those are the two syntaxes that
 *      Horde_Argv supports.
 *    _long_opt_fmt : str
 *      similar but for long options; must be either "%s %s" ("--file FILE")
 *      or "%s=%s" ("--file=FILE").
 *
 * @category  Horde
 * @package   Argv
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Mike Naberezny <mike@maintainable.com>
 * @copyright 2010-2017 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 */
abstract class Horde_Argv_HelpFormatter
{
    const NO_DEFAULT_VALUE = 'none';

    public $parser = null;
    public $_color;
    public $indent_increment;
    public $max_help_position;
    public $help_position;
    public $width;
    public $level;
    public $current_indent;
    public $help_width;
    public $default_tag;
    public $option_strings;
    public $_short_opt_fmt;
    public $_long_opt_fmt;
    public $short_first;

    public function __construct(
        $indent_increment, $max_help_position, $width = null,
        $short_first = false, $color = null
    )
    {
        if (is_null($color)) {
            $color = new Horde_Cli_Color();
        }
        $this->_color = $color;
        $this->indent_increment = $indent_increment;
        $this->help_position = $this->max_help_position = $max_help_position;
        if (is_null($width)) {
            $cli = new Horde_Cli();
            $width = $cli->getWidth() - 2;
        }
        $this->width = $width;
        $this->current_indent = 0;
        $this->level = 0;
        $this->help_width = null; // computed later
        $this->short_first = $short_first;
        $this->default_tag = '%default';
        $this->option_strings = [];
        $this->_short_opt_fmt = '%s %s';
        $this->_long_opt_fmt = '%s=%s';
    }

    public function setParser($parser)
    {
        $this->parser = $parser;
    }

    public function setShortOptDelimiter($delim)
    {
        if (!in_array($delim, array('', ' '))) {
            throw new InvalidArgumentException('invalid metavar delimiter for short options: ' . $delim);
        }
        $this->_short_opt_fmt = "%s$delim%s";
    }

    public function setLongOptDelimiter($delim)
    {
        if (!in_array($delim, array('=', ' '))) {
            throw new InvalidArgumentException('invalid metavar delimiter for long options: ' . $delim);
        }
        $this->_long_opt_fmt = "%s$delim%s";
    }

    public function indent()
    {
        $this->current_indent += $this->indent_increment;
        $this->level += 1;
    }

    public function dedent()
    {
        $this->current_indent -= $this->indent_increment;
        assert($this->current_indent >= 0); // Indent decreased below 0
        $this->level -= 1;
    }

    abstract public function formatUsage($usage);

    abstract public function formatHeading($heading);

    /**
     * Highlights with the heading color.
     *
     * @since Horde_Argv 2.1.0
     *
     * @param string $heading  A heading text.
     *
     * @retun string  The colored text.
     */
    public function highlightHeading($heading)
    {
        return $this->_color->brown($heading);
    }

    /**
     * Format a paragraph of free-form text for inclusion in the
     * help output at the current indentation level.
     */
    protected function _formatText($text)
    {
        $text_width = $this->width - $this->current_indent;
        $indent = str_repeat(' ', $this->current_indent);
        return wordwrap($indent . $text, $text_width, "\n" . $indent, true);
    }

    public function formatDescription($description)
    {
        if ($description) {
            return $this->_formatText($description) . "\n";
        } else {
            return '';
        }
    }

    public function formatEpilog($epilog)
    {
        if ($epilog) {
            return "\n" . $this->_formatText($epilog) . "\n";
        } else {
            return '';
        }
    }

    public function expandDefault($option)
    {
        if (is_null($this->parser) || !$this->default_tag) {
            return $option->help;
        }

        $default_value = isset($this->parser->defaults[$option->dest]) ? $this->parser->defaults[$option->dest] : null;
        if ($default_value == Horde_Argv_Option::$NO_DEFAULT || !$default_value) {
            $default_value = self::NO_DEFAULT_VALUE;
        }

        return str_replace($this->default_tag, (string)$default_value, $option->help);
    }

    /**
     * The help for each option consists of two parts:
     *   * the opt strings and metavars
     *     eg. ("-x", or "-fFILENAME, --file=FILENAME")
     *   * the user-supplied help string
     *     eg. ("turn on expert mode", "read data from FILENAME")
     *
     * If possible, we write both of these on the same line:
     *   -x      turn on expert mode
     *
     * But if the opt string list is too long, we put the help
     * string on a second line, indented to the same column it would
     * start in if it fit on the first line.
     *   -fFILENAME, --file=FILENAME
     *           read data from FILENAME
     */
    public function formatOption($option)
    {
        $result = array();
        $opts = isset($this->option_strings[(string)$option])
            ? $this->option_strings[(string)$option]
            : null;
        $opt_width = $this->help_position - $this->current_indent - 2;
        if (strlen($opts) > $opt_width) {
            $opts = sprintf(
                '%' . $this->current_indent . "s%s\n",
                '',
                $opts
            );
            $indent_first = $this->help_position;
        } else {
            // start help on same line as opts
            $opts = sprintf(
                '%' . $this->current_indent . 's%-' . $opt_width . 's  ',
                '',
                $opts
            );
            $indent_first = 0;
        }
        $opts = $this->highlightOption($opts);
        $result[] = $opts;
        if ($option->help) {
            $help_text = $this->expandDefault($option);
            $help_lines = explode("\n", wordwrap($help_text, $this->help_width, "\n", true));
            $result[] = sprintf(
                '%' . $indent_first . "s%s\n", '', $help_lines[0]
            );
            for ($i = 1, $i_max = count($help_lines); $i < $i_max; $i++) {
                $result[] = sprintf(
                    '%' . $this->help_position . "s%s\n", '', $help_lines[$i]
                );
            }
        } elseif (substr($opts, -1) != "\n") {
            $result[] = "\n";
        }
        return implode('', $result);
    }

    /**
     * Highlights with the option color.
     *
     * @since Horde_Argv 2.1.0
     *
     * @param string $option  An option text.
     *
     * @retun string  The colored text.
     */
    public function highlightOption($option)
    {
        return $this->_color->green($option);
    }

    public function storeOptionStrings($parser)
    {
        $this->indent();
        $max_len = 0;
        foreach ($parser->optionList as $opt) {
            $strings = $this->formatOptionStrings($opt);
            $this->option_strings[(string)$opt] = $strings;
            $max_len = max(
                $max_len,
                strlen($strings) + $this->current_indent
            );
        }
        $this->indent();
        foreach ($parser->optionGroups as $group) {
            foreach ($group->optionList as $opt) {
                $strings = $this->formatOptionStrings($opt);
                $this->option_strings[(string)$opt] = $strings;
                $max_len = max(
                    $max_len,
                    strlen($strings)
                        + $this->current_indent
                );
            }
        }
        $this->dedent();
        $this->dedent();
        $this->help_position = min($max_len + 2, $this->max_help_position);
        $this->help_width = $this->width - $this->help_position;
    }

    /**
     * Return a comma-separated list of option strings & metavariables.
     */
    public function formatOptionStrings($option)
    {
        if ($option->takesValue()) {
            $metavar = $option->metavar ? $option->metavar : Horde_String::upper($option->dest);
            $short_opts = array();
            foreach ($option->shortOpts as $sopt) {
                $short_opts[] = sprintf($this->_short_opt_fmt, $sopt, $metavar);
            }
            $long_opts = array();
            foreach ($option->longOpts as $lopt) {
                $long_opts[] = sprintf($this->_long_opt_fmt, $lopt, $metavar);
            }
        } else {
            $short_opts = $option->shortOpts;
            $long_opts = $option->longOpts;
        }

        if ($this->short_first) {
            $opts = array_merge($short_opts, $long_opts);
        } else {
            $opts = array_merge($long_opts, $short_opts);
        }

        return implode(', ', $opts);
    }

}
