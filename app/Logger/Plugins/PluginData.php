<?php


namespace App\Logger\Plugins;

class PluginData
{

    protected string $plugin;

    protected string $uiLabel;
    protected string $cliLabel;

    protected array $details;

    public static function make(): self
    {
        return new PluginData;
    }

    public function toArray(): array
    {
        return [
            'plugin' => $this->plugin,
            'uiLabel' => $this->uiLabel,
            'cliLabel' => $this->cliLabel,
            'details' => $this->details
        ];
    }

    public function setPlugin(string $plugin): self
    {
        $this->plugin = $plugin;
        return $this;
    }

    public function setUiLabel(string $uiLabel): self
    {
        $this->uiLabel = $uiLabel;
        return $this;
    }

    public function setCliLabel(string $cliLabel): self
    {
        $this->cliLabel = $cliLabel;
        return $this;
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;
        return $this;
    }

    public function getCliLabel(): string
    {
        return $this->cliLabel;
    }

}
