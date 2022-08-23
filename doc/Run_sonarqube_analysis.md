# Running SonarQube analysis

1. On a separate console, launch the SonarQube server.

    **On first run:**

    ```bash
    sudo docker run -d --name sonarqube -p 9000:9000 sonarqube
    sudo docker stop sonarqube
    ```
   
    **On the following runs:**

    ```bash
    sudo docker start sonarqube
    sudo docker stop sonarqube
    ```

2. Login to `localhost:9000` with user `admin` and pass `admin`.

3. On another console, located on the project root folder, run the SonarQube client.

    ```bash
    /path/to/SonarQube/sonar-scanner-4.2.0.1873-linux/bin/sonar-scanner
    ```
