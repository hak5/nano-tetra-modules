<?php namespace pineapple;

/*
 * Author: trashbo4t (github.com/trashbo4t)
 */

class Themes extends Module
{
    // CONSTANTS
    private $MODULE_DIR = '/pineapple/modules/Themes/';
    private $CSS_DIR = '/pineapple/modules/Themes/css/';
    private $SKELETON_CSS = '/pineapple/modules/Themes/css/main.css';
    private $CURRENT_CSS = '/pineapple/modules/Themes/css/CURRENT_CSS';
    private $CURRENT_LOGO = '/pineapple/modules/Themes/img/CURRENT_LOGO';
    private $CURRENT_THROBBER = '/pineapple/modules/Themes/img/CURRENT_THROBBER';
    private $CURRENT_FAVICON = '/pineapple/modules/Themes/img/CURRENT_FAVICON';
    private $CURRENT_DASHBOARD = '/pineapple/modules/Themes/img/CURRENT_DASHBOARD';
    private $BACKUP_MAIN_CSS = '/pineapple/css/.backup.main.css';
    private $BACKUP_FAVICON = '/pineapple/img/.backup.favicon.ico';
    private $BACKUP_THROBBER = '/pineapple/img/.backup.throbber.gif';
    private $BACKUP_LOGO = '/pineapple/img/.backup.logo.png';
    private $BACKUP_DASHBOARD = '/pineapple/modules/Dashboard/.backup.module_icon.svg';
   
    private $current_theme = "";

    /*
    * Static list of all module names
    * from here we can grab the CURRENT file, and the module directory
    */
    private $ALL_MODULES = array(
        "Dashboard",
        "Recon",
        "Clients",
        "ModuleManager",
        "Filters",
        "PineAP",
        "Tracking",
        "Logging",
        "Reporting",
        "Networking",
        "Configuration",
        "Advanced",
        "Notes",
        "Help",
    );
    /*
    * Faster to map Hex values then to calculate on the fly
    */
    private $WHITE_HEX = 'ffffff';
    private $RESTORE_HEX = '808080';
    private $NORMAL_COLOR_HEX_MAP = array(
        "red"    => "FF0000",
        "green"  => "00FF00",
        "blue"   => "0000FF",
        "purple" => "800080",
        "orange" => "cc3300",
        "yellow" => "ffff00",
        "pink"   => "ff0066",
    );
    private $LIGHT_COLOR_HEX_MAP  = array(
        "red"    => "ff4d4d",
        "green"  => "80ff80",
        "blue"   => "8080ff",
        "purple" => "ff66ff",
        "orange" => "ff9f80",
        "yellow" => "ffff66",
        "pink"   => "ff99c2",
    );
    private $DARK_COLOR_HEX_MAP  = array(
        "red"    => "990000",
        "green"  => "004d00",
        "blue"   => "000077",
        "purple" => "4d004d",
        "orange" => "992600",
        "yellow" => "cccc00",
        "pink"   => "99003d",
    );

    public function route()
    {
        switch ($this->request->action) {
            case 'getThemeList':
                $this->handleGetThemeList();
                break;
            case 'themeFields':
                $this->getThemeFields();
                break;
            case 'deleteTheme':
                $this->handleDeleteTheme();
                break;
            case 'activateTheme':
                $this->activateTheme();
                break;
            case 'getThemeCode':
                $this->getThemeCode();
                break;
            case 'submitThemeCode':
                $this->submitThemeCode();
                break;
            case 'getCurrentTheme':
	            $this->getCurrentTheme();
		        break;
            case 'createNewTheme':
                $this->handleCreateNewTheme();
                break;
            case 'restoreDefault':
                $this->restoreDefault();
                break;
            case 'backupFiles':
                $this->backupFiles();
		        break;
	        case 'replaceImage':
    	    	$this->replaceImage();
	    	    break;
	    }
    }
    // Get the CURRENT_<MODULE_ICON> file, which is 1 line with the current color of the icon
    public function currentFile($name)
    {
        $upper = strtoupper($name);
        return "/pineapple/modules/Themes/img/CURRENT_{$upper}";
    }
    // Move an image from light->dark or vice versa
    public function replaceImage() 
    {
        $img = $this->request->img;
	    switch ($img) 
	    {
            // Pineapple Logo
	        case 'Logo':
		        $this->response = array("message" => "Logo Changed");
		        if ($this->request->light) {
        			exec("cp $this->BACKUP_LOGO /pineapple/img/logo.png");
                    exec("echo light > $this->CURRENT_LOGO");
                }
                else 
                {
                    exec("echo dark > $this->CURRENT_LOGO");
                    exec('cp /pineapple/modules/Themes/img/logo-dark.png /pineapple/img/logo.png');
                }
                $this->response = array("message" => "Logo Changed");
                break;

                // Pineapple favicon.ico Image
            case 'Icon':
                if ($this->request->light) {
                    exec("echo light > $this->CURRENT_FAVICON");
                    exec("cp $this->BACKUP_FAVICON /pineapple/img/favicon.ico");
                }
                else 
                {
                    exec("echo dark > $this->CURRENT_FAVICON");
                    exec('cp /pineapple/modules/Themes/img/favicon-dark.ico /pineapple/img/favicon.ico');
                }
                $this->response = array("message" => "Icon Changed");
                break;

            // Pineapple Throbber gif
            case 'Throbber':
                if ($this->request->light) {
                    exec("echo light > $this->CURRENT_THROBBER");
                    exec("cp $this->BACKUP_THROBBER /pineapple/img/throbber.gif");
                }
                else 
                {
                    exec("echo dark > $this->CURRENT_THROBBER");
                    exec('cp /pineapple/modules/Themes/img/throbber-dark.gif /pineapple/img/throbber.gif');
                }
                $this->response = array("message" => "Throbber Changed");
                break;

            // Modify all of the module Icons
            case 'All':
                foreach ($this->ALL_MODULES as $module) 
                {
                    $current = $this->currentFile($module);
                    $success = $this->replaceModuleImage(
                        $module,
                        $this->request->color,
                        $this->request->brightness
                    );
                }
                $this->response = array(
                    "success" => true,
                    "message" => "All module icons changed to {$this->request->color}-{$this->request->brightness}"
                );
                break;
            // Assume module Icon
            default:
                $success = $this->replaceModuleImage(
                    $this->request->img,
                    $this->request->color,
                    $this->request->brightness
                );
                $this->response = array(
                    "success" => $success,
                    "message" => "{$this->request->img} icon changed to {$this->request->color}-{$this->request->brightness}"
                );
                break;
        }
    }
    /*
    * replaceModuleImage
    * $moduleName -> String name of module, can be any format (nEtWoRkIng) because it gets formatted
    * $color -> string name of the color, used for index of mapping
    * $brightness -> string name of brightness, used for map selection
    *
    * This is a neat little technique to change image colors, since the images are SVG we search and 
    * replace the original RBG colors in the image file with the new RBG hex values.
    */
    public function replaceModuleImage($moduleName, $color, $brightness)
    {
        $current = $this->currentFile($moduleName);
        $replace = "/pineapple/modules/{$moduleName}/module_icon.svg";
        switch($color)
        {
            case 'light':
                return $this->restoreModuleIcon (
                    $moduleName
                );
                break;
            case 'dark':
                if (exec("echo dark > $current") != 0 ||
                        exec("echo $brightness >> $current") != 0 || 
                            !$this->searchAndReplaceFile($replace, "FFFFFF"))
                {
                    return false;
                }
                break;
            default:
                $hex = "";
                switch($brightness)
                {
                    case 'light':
                        $hex = $this->LIGHT_COLOR_HEX_MAP[$color];
                        break;
                    case 'dark':
                        $hex = $this->DARK_COLOR_HEX_MAP[$color];
                        break;
                    default:
                        $hex = $this->NORMAL_COLOR_HEX_MAP[$color];
                        break;
                }
                // Replace the modules icon image
                if (exec("echo $color > $current") != 0 || 
                        exec("echo $brightness >> $current") != 0)
                {
                    return false;
                }
                if (!$this->searchAndReplaceFile($replace, $hex)) {
                    return false;
                }
                break;
        }
        return true;
    }
    /*
    *  searchAndReplaceFile
    *  $s -> substring to find
    *  return: true or false showing succcessful string replacement
    */
    public function searchAndReplaceFile($f, $s) 
    {
        // Use a stream editor so we dont have to load the entire file into RAM
        return (exec("sed -i 's/fill:\(.*\);/fill:#{$s};/g' $f") == 0);
    }
    /*
    *  setCurrentTheme
    *  $theme -> modify CURRENT_CSS file with new theme
    */
    public function setCurrentTheme($theme)
    {
        $this->current_theme = $theme;
	    exec('echo '.$theme.' > /pineapple/modules/Themes/css/CURRENT_CSS');
    }
    /*
    *  getCurrentTheme
    *  return current theme, and all parameters for icon colors/brightness
    */
    public function getCurrentTheme() 
    {
    	$line = file('/pineapple/modules/Themes/css/CURRENT_CSS')[0];
    	$line = trim(preg_replace('/\s+/', ' ', $line));

	    $logo = file('/pineapple/modules/Themes/img/CURRENT_LOGO')[0];
	    $logo = trim(preg_replace('/\s+/', ' ', $logo));

    	$icon = file('/pineapple/modules/Themes/img/CURRENT_FAVICON')[0];
	    $icon = trim(preg_replace('/\s+/', ' ', $icon));

	    $throbber = file('/pineapple/modules/Themes/img/CURRENT_THROBBER')[0];
        $throbber = trim(preg_replace('/\s+/', ' ', $throbber));
        $this->response = array(
            "current" => $line, 
            "logo" => $logo, 
            "icon" => $icon, 
            "throbber" => $throbber,
        );
        foreach ($this->ALL_MODULES as $module) 
        {
            $current = $this->currentFile($module);
            $lower = strtolower($module);
            $color = file($current)[0];
            $color = trim(preg_replace('/\s+/', ' ', $color));
            $brightness = file($current)[1];
            $brightness = trim(preg_replace('/\s+/', ' ', $brightness));
            $this->response[$lower] = $color;
            $this->response[$lower.'brightness'] = $brightness;
        }
    }
    /*
    *  isCurrentThemeEnv
    *  $theme string name of theme to check if its current
    *  check if global current_them var is set, compare against that
    *  this way we dont open,read,close a file every for every check
    */
    public function isCurrentThemeEnv($theme)
    {
        if ($this->current_theme != "") {
            return ($this->current_theme == $theme);
        }
        if (!file_exists($this->CURRENT_CSS)) {
		    return false;
	    }
        $line = file($this->CURRENT_CSS)[0];
    	$line = trim(preg_replace('/\s+/', ' ', $line));
	    return ($line === $theme);
    }
    /* 
    *  restoreImages
    *  Undo any changes made by this Module
    *  This includes: original icons, gifs, svg's
    */
    public function restoreImages() 
    {
        $success = true;
        exec("cp {$this->BACKUP_FAVICON} /pineapple/img/favicon.ico");
        exec("cp {$this->BACKUP_LOGO} /pineapple/img/logo.png");
        exec("cp {$this->BACKUP_THROBBER} /pineapple/img/throbber.gif");
	    exec('echo light > /pineapple/modules/Themes/img/CURRENT_LOGO');
	    exec('echo light > /pineapple/modules/Themes/img/CURRENT_FAVICON');
        exec('echo light > /pineapple/modules/Themes/img/CURRENT_THROBBER');
        
        foreach ($this->ALL_MODULES as $module) 
        {
            $current = $this->currentFile($module);
            $success = $this->restoreModuleIcon ( 
                $module
            );
        }
        $this->response = array(
            "success" => $success, 
            "message" => "Restored all files"
        );
    }
    /* 
    *  restoreModuleIcon
    *  Generic helper function to put a modules icon back to normal
    *  using only the name of the module (in any format).
    */
    public function restoreModuleIcon($moduleName) 
    {
        $current = $this->currentFile($moduleName);
        $replace = "/pineapple/modules/{$moduleName}/module_icon.svg";
        if (!$this->searchAndReplaceFile($replace, $this->RESTORE_HEX))
        {
            return false;
        }
        if (exec("echo light > $current") != 0 ||
            exec("echo normal >> $current") != 0)
        {
            return false;
        }
        return true;
    }
    /*
    *  restoreDefault
    *  backup all files if not done yet
    *  put the original css file back
    *  restore all of the images
    */
    public function restoreDefault()
    {
        $this->backupFiles();
   	    exec("cp {$this->BACKUP_MAIN_CSS} /pineapple/css/main.css");
	    $this->setCurrentTheme('main.css');
	    $this->restoreImages();
    }
    /*
    *  getThemeCode
    *  retrieve the css styling code from a theme file
    */
    public function getThemeCode()
    {
        $code = file_get_contents($this->CSS_DIR . $this->request->name);
        $this->response = array("code" => $code, "file" => $this->CSS_DIR . $this->request->name);
    }
    /*
    *  getThemeFields
    *  more or less only returns the code for now
    */
    public function getThemeFields()
    {
	    $allFields = array();
        $code = file_get_contents($this->CSS_DIR . $this->request->name);
        $this->response = array("code" => $code);
    }
    /*
    *  activateTheme
    *  mv the users selected theme to main.css file
    */
    public function activateTheme()
    {
        $themeName = $this->request->name;
        $cmd = exec("cp {$this->CSS_DIR}{$themeName} /pineapple/css/main.css");
	    if ($cmd == 0) {
    		$this->setCurrentTheme($themeName);
		    $message = $themeName . " is now active.";
        	$this->response = array("return" => true, "message" => $message);
	    }
	    else
	    {
    		$message = "Could not move theme" . $themeName . "(Something is wrong..)";
        	$this->response = array("return" => false,"message" => $message);
	    }
    }
    /* Credits to SteveRusin at http://php.net/manual/en/ref.strings.php */
    private function endsWith($str, $sub)
    {
        return (substr($str, strlen($str) - strlen($sub)) === $sub);
    }
    /*
    * handleDeleteTheme
    * delete a users theme file from the local css directory
    */
    public function handleDeleteTheme()
    {
        $themeName = $this->request->name;
	    exec("rm {$this->CSS_DIR}{$themeName}");
	    if (!file_exists("/pineapple/modules/Themes/css/" . $themeName)) {
            $message = "Deleted " . $themeName;
        } else {
            $message = "Error deleting " . $themeName;
        }
        $this->response = array("message" => $message);
    }
    /*
    *  submitThemeCode
    *  save a users theme file in the local css directory
    */
    public function submitThemeCode()
    {
	$code = $this->request->themeCode;
        $themeName = $this->request->name;
        $fileName = $this->request->fileName;
        file_put_contents($this->CSS_DIR . $themeName, $code);
        $message = (!file_exists($this->CSS_DIR . $themeName)) ? "Created " . $themeName : "Updated " . $themeName;
        
        $this->response = array(
            "message" => $message,
	    "filename" => $fileName
        );
   }
   /*
   *  handleGetThemeList
   *  get the list of .css files in the local css directory
   *  avoid sending back the main.css file so it cannot be modified
   */
   public function handleGetThemeList()
   {
    	$all_themes = array();
        $root_themes = preg_grep('/^([^.])/', scandir("{$this->CSS_DIR}"));
        foreach ($root_themes as $theme) {
            if (!is_file($theme) && $this->endsWith($theme, '.css') && $theme != "main.css") {
                $active = $this->isCurrentThemeEnv($theme);
                $obj = array("title" => $theme, "location" => "../Themes/css/", "active" => $active);
                array_push($all_themes, $obj);
            }
        }
        $this->response = $all_themes;
    }
    /*
    *  handleCreateNewTheme
    *  create a new .css theme file in the local css directory
    */
    public function handleCreateNewTheme()
    {
    	$themePath = $this->CSS_DIR;
        $themeName = str_replace(' ', '_', $this->request->themeName);
	    if (!$this->endswith($themeName, '.css')) {
    		$themeName = $themeName . ".css";
	    }
        if (file_exists($themePath . $themeName)) {
            $this->response = array("create_success" => false, "create_message" => "A theme named {$themeName} already exists.");
	    return;
        }
        exec("cp {$this->SKELETON_CSS} {$themePath}{$themeName}");
        $this->response = array("create_success" => true, "create_message" => "Created {$themeName}");
    }
    /*
    * backupFiles
    * Backup all of the .css/IMG files used so the module can properly restore defaults
    */
    public function backupFiles()
    {
        $success = true;
        $modules = array();
        if (!file_exists($this->BACKUP_MAIN_CSS)) {
            exec("cp /pineapple/css/main.css {$this->BACKUP_MAIN_CSS}");
            array_push($modules, "Backed up main.css.");
        }
        if (!file_exists($this->SKELETON_CSS)) {            
	        mkdir($this->CSS_DIR);
            exec("cp {$this->BACKUP_MAIN_CSS} {$this->SKELETON_CSS}");
            array_push($modules, "Backed up skeleton.css.");
        }
	    if (!file_exists($this->BACKUP_THROBBER)) {
            exec("cp /pineapple/img/throbber.gif {$this->BACKUP_THROBBER}");
            array_push($modules, "Backed up favicon.ico");
        }
        if (!file_exists($this->CURRENT_THROBBER)) {
            exec("echo light > $this->CURRENT_THROBBER");
            array_push($modules, "Wrote to {$this->CURRENT_THROBBER}");
        }
        if (!file_exists($this->BACKUP_FAVICON)) {
            exec("cp /pineapple/img/favicon.ico {$this->BACKUP_FAVICON}");
            array_push($modules, "Backed up favicon.ico");
        }
        if (!file_exists($this->CURRENT_FAVICON)) {
            exec("echo light > $this->CURRENT_FAVICON");
            array_push($modules, "Wrote to /pineapple/modules/Themes/img/CURRENT_FAVICON");
        }
	    if (!file_exists($this->BACKUP_LOGO)) {
            exec("cp /pineapple/img/logo.png $this->BACKUP_LOGO");
            array_push($modules, "Wrote to {$this->BACKUP_LOGO}");
        }
        if (!file_exists($this->CURRENT_LOGO)) {
	        exec("echo light > $this->CURRENT_LOGO");
            array_push($modules, "Wrote to {$this->CURRENT_LOGO}");
        }
        foreach ($this->ALL_MODULES as $module) 
        {
            $current = $this->currentFile($module);
            if (!$this->backupModuleIcon($current))
            {
                array_push($modules, "Did not write to {$current}.");
            } 
            else 
            {
                array_push($modules, "Wrote to {$current}.");
            }
        }
        $this->response = array(
            "success" => $success, 
            "message" => $success ? 
                "Created a backup file for all files" : 
                    "Failed to backup files! Tread lightly",
            "modules" => $modules
        );
    }
    public function backupModuleIcon($currentFile) {
        if (!file_exists($currentFile)) {
            if (exec("echo light > $currentFile") != 0 || 
                exec("echo normal >> $currentFile") != 0) 
            {
                return false;
            }
            return true;
        }
        return false;
    }
}
