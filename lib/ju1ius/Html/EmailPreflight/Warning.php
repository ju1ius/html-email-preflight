<?php

namespace ju1ius\Html\EmailPreflight;

class Warning
{
  const
    LEVEL_NONE = 0,
    LEVEL_SAFE = 1,
    LEVEL_POOR = 2,
    LEVEL_RISKY = 3;

  protected static
    $LABELS = array(
      'NONE', 'SAFE', 'POOR', 'RISKY'
    );

  private
    $level,
    $name,
    $clients,
    $support_level;

  public function __construct($level, $name, $clients, $support_level='')
  {
    $this->level = $level;
    $this->name = $name;
    $this->clients = $clients;
    $this->support_level = $support_level;
  }

  public function getMessage()
  {
    return Warning::$LABELS[$this->level]
      . ( $this->support_level ? " ({$this->support_level})" : '' )
      . ' => ' . $this->name
      . ' is unsupported in: ' . implode(', ', $this->clients);
  }

  public function getLevel()
  {
    return $this->level;
  }
  public function getLabel()
  {
    return Warning::$LABELS[$this->level];
  }
  public function getName()
  {
    return $this->name;  
  }
  public function getClients()
  {
    return $this->clients;  
  }
  public function getSupportLevel()
  {
    return $this->support_level;  
  }

  public function __toString()
  {
    return $this->getMessage();
  }
}
