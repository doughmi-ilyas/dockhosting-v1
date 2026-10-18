<?php
require_once __DIR__ . '/../php/connect.php';

Class Project extends db{
    
    public function __construct() {
        parent::__construct();
    }

    public function getProjects($user_id){
        $query = "SELECT * FROM Project WHERE user_id =  :user_id";
        $stmt = $this->connect()->prepare($query);
        $stmt->bindParam(":user_id",$user_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContainersCount($user_id){
        $query = "SELECT COUNT(*) as count FROM Project WHERE user_id = :user_id";
        $stmt = $this->connect()->prepare($query);
        $stmt->bindParam(":user_id",$user_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function trackPort(){
        $port_query = "SELECT port FROM Project ORDER BY port DESC limit 1;";
        $stmt = $this->connect()->prepare($port_query);
        $stmt->execute();  
        $result = $stmt->fetch(PDO::FETCH_ASSOC);  


        if(!$result){
            return false;
        }
        else{
            return $result["port"];
        }
    }

    public function createProject($project_name,$port,$container_name,$user_id, $type = 'php'){
        $create_query = "INSERT INTO Project(project_name,port,container_name,status,user_id, type) 
                        VALUES(:project_name,:port,:container_name,:status,:user_id, :type)";

        $stmt = $this->connect()->prepare($create_query);
        $stmt->bindParam(":project_name",$project_name);
        $stmt->bindParam(":container_name",$container_name);
        $stmt->bindParam(":port",$port);
        $stmt->bindValue(":status","running");
        $stmt->bindParam(":user_id",$user_id);
        $stmt->bindParam(":type", $type);
        return $stmt->execute();
    }
    
    public function getContainerLogs($container_name) {
        $cmd = "docker logs --tail 100 " . escapeshellarg($container_name) . " 2>&1";
        return shell_exec($cmd);
    }

    public function getContainerStats($container_name) {
        $cmd = "docker stats --no-stream --format \"{{json .}}\" " . escapeshellarg($container_name);
        $output = shell_exec($cmd);
        
        if ($output === null || trim($output) === '') {
            return null;
        }
        
        return json_decode($output, true);
    }

    public function stopContainer($container_name){
        if (!$this->ownsContainer($container_name)) return false;
        shell_exec("docker stop " . escapeshellarg($container_name));
        $stop_query = "UPDATE Project SET status = 'stopped' WHERE user_id = :user_id AND container_name = :container_name";

        $stmt = $this->connect()->prepare($stop_query);
        $stmt->bindParam(":user_id",$_SESSION["id"]);
        $stmt->bindParam(":container_name",$container_name);
        $result = $stmt->execute();

        if($result){
            return $result;
        }
        else{
            return false;
        }
    }

    public function startContainer($container_name){
        if (!$this->ownsContainer($container_name)) return false;
        shell_exec("docker start " . escapeshellarg($container_name));
        $start_query = "UPDATE Project SET status = 'running' WHERE user_id = :user_id AND container_name = :container_name";

        $stmt = $this->connect()->prepare($start_query);
        $stmt->bindParam(":user_id",$_SESSION["id"]);
        $stmt->bindParam(":container_name",$container_name);
        $result = $stmt->execute();

        if($result){
            return $result;
        }
        else{
            return false;
        }
    }
    
    public function restartContainer($container_name){
        if (!$this->ownsContainer($container_name)) return false;
        shell_exec("docker restart " . escapeshellarg($container_name));
        $start_query = "UPDATE Project SET status = 'running' WHERE user_id = :user_id AND container_name = :container_name";

        $stmt = $this->connect()->prepare($start_query);
        $stmt->bindParam(":user_id",$_SESSION["id"]);
        $stmt->bindParam(":container_name",$container_name);
        $result = $stmt->execute();

        if($result){
            return $result;
        }
        else{
            return false;
        }
    }
    public function ownsContainer($container_name){
        if (!isset($_SESSION["id"]) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_.-]*$/', (string)$container_name)) {
            return false;
        }
        $stmt = $this->connect()->prepare("SELECT 1 FROM Project WHERE user_id = :user_id AND container_name = :container_name");
        $stmt->execute([":user_id" => $_SESSION["id"], ":container_name" => $container_name]);
        return (bool)$stmt->fetchColumn();
    }

    // Resolves $rel inside the current user's project folder; false if not owned or it escapes the folder.
    // $must_exist = false resolves the parent instead, for paths about to be created.
    public function resolvePath($container_name, $rel = "", $must_exist = true){
        if (!$this->ownsContainer($container_name)) {
            return false;
        }
        $root = realpath(dirname(__DIR__) . "/users/Projects/" . $_SESSION["id"] . "/" . $container_name);
        if ($root === false) {
            return false;
        }
        $path = $root . "/" . $rel;
        $name = basename($path);
        if (!$must_exist && in_array($name, ["", ".", ".."], true)) {
            return false;
        }
        $real = realpath($must_exist ? $path : dirname($path));
        if ($real === false || ($real !== $root && strpos($real, $root . DIRECTORY_SEPARATOR) !== 0)) {
            return false;
        }
        return $must_exist ? $real : $real . DIRECTORY_SEPARATOR . $name;
    }

    public function deleteProject($container_name,$file_dir){
        if (!$this->ownsContainer($container_name)) return false;
        $this->removeContainer($container_name);
        $this->removeDir($file_dir);
        $this->removeDatabase($container_name);
    }

    private function removeContainer($container){
        shell_exec("docker rm -f " . escapeshellarg($container));
    }
    private function removeDir($file_dir){
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            shell_exec("rmdir /s /q " . escapeshellarg($file_dir));
        } else {
            shell_exec("rm -rf " . escapeshellarg($file_dir));
        }
    }
    private function removeDatabase($container_name){
        $remove_query = "DELETE FROM Project WHERE user_id = :user_id AND container_name = :container_name";
        $stmt = $this->connect()->prepare($remove_query);
        $stmt->bindParam(":container_name",$container_name);
        $stmt->bindParam(":user_id",$_SESSION["id"]);
        $result = $stmt->execute();

        if($result){
            return $result;
        }
        else{
            return false;
        }
    }
    public function getProjectFiles($container_name,$sub_path = ""){
        $path = $this->resolvePath($container_name, $sub_path);
        if ($path === false || !is_dir($path)) {
            return [];
        }
        $path .= "/";

        $files = scandir($path);
        $result = [];
        foreach($files as $file){
            if($file == "." || $file == ".." || $file == ".htaccess" || $file == ".git"){
                continue;
            }

            $item_path = $path . $file;
            
            if(is_dir($item_path)){
                $type = "folder";
            }
            else{
                $type = "file";
            }

            $result[] = ["name" => $file,"type"=>$type];

        }

        return $result;
    }

    public function getFileContent($container_name,$file_name){
        $path = $this->resolvePath($container_name, $file_name);

        if($path !== false && is_file($path)){
            return file_get_contents($path);
        }
        else{
            return "file not found";
        } 
    }

    public function saveFileChanges($container_name,$file_name,$new_content){
        $path = $this->resolvePath($container_name, $file_name);
        if($path !== false && is_file($path)){
            file_put_contents($path,$new_content);
        }
        else{
            return "file not found";
        }
    }

    public function deleteItem($container_name, $file_path) {
        $path = $this->resolvePath($container_name, $file_path);
        if ($path === false || $path === $this->resolvePath($container_name)) {
            return false;
        }

        if (basename($path) === 'error.log') {
            return false;
        }

        if (is_dir($path)) {
            $this->removeDir($path);
            return true;
        } elseif (is_file($path)) {
            return unlink($path);
        }
        return false;
    }

    

    public function getAllProjects() {
        $query = "SELECT Project.*, users.username FROM Project LEFT JOIN users ON Project.user_id = users.id ORDER BY created_at DESC";
        $stmt = $this->connect()->query($query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adminStopContainer($container_name) {
        shell_exec("docker stop " . escapeshellarg($container_name));
        $query = "UPDATE Project SET status = 'stopped' WHERE container_name = :container_name";
        $stmt = $this->connect()->prepare($query);
        $stmt->bindParam(":container_name", $container_name);
        return $stmt->execute();
    }

    public function adminStartContainer($container_name) {
        shell_exec("docker start " . escapeshellarg($container_name));
        $query = "UPDATE Project SET status = 'running' WHERE container_name = :container_name";
        $stmt = $this->connect()->prepare($query);
        $stmt->bindParam(":container_name", $container_name);
        return $stmt->execute();
    }

    public function adminDeleteProject($container_name, $user_id) {
        // Trust the DB, not the posted user_id/name, for the folder we rm -rf.
        $stmt = $this->connect()->prepare("SELECT user_id FROM Project WHERE container_name = :container_name");
        $stmt->execute([":container_name" => $container_name]);
        $owner = $stmt->fetchColumn();
        if ($owner === false) {
            return false;
        }
        $this->removeContainer($container_name);

        $base_path = dirname(__DIR__) . "/users/Projects/";
        $path = $base_path . (int)$owner . "/" . $container_name;
        $this->removeDir($path);

        $query = "DELETE FROM Project WHERE container_name = :container_name";
        $stmt = $this->connect()->prepare($query);
        $stmt->bindParam(":container_name", $container_name);
        return $stmt->execute();
    }
}

