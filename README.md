# Zebra-printers-management-tool
A web tool to manage Zebra label printers on a LAN

INTRODUCTION
ZPMT is a web application developed in PHP which sits on a MySQL database that can be used to manage Zebra printers in a LAN. With ZPMT it is possible to scan a subnet (the same subnet where ZPMT is installed) to search for Zebra printers, add a single printer by IP or use a CSV file to load the IP addresses, a hostname and, in case, add automatically a printer to a pre-created group. Each printer can be assigned to a max of 10 groups and the groups can be used to separate the printers according to their physical location, their use or any other type of grouping rule.
Once inserted into the inventory, the printers can also be assigned to a label type and a ribbon type in order to keep track of the usage of the label and ribbon rolls and, possibly, prevent supply issues. When the printer is online, it is possible to access to the printer detail page where it is shown the printer configuration and where you can edit or save its configuration as a resource which can then be sent to a group of printers. The resources also include firmware which can be uploaded and sent to the printers. 
The tool also regularily check the battery and the printhead health and generates a notification if the battery health is below 80% or if at least a single printhead elements fails the health check.

REQUIREMENTS
- A web server. The apache webserver that is installed with XAMPP works pretty well, but any other web server (installed on a NAS for example) on your network, works as well.
- PHP v. 8.0.17-0101 or above with the socket extension enabled.
- MySQL v. 4.9.7-1032 or above

The cron.php file
In order to keep up to date the media counter, create and execute the scheduled task to reset the odometer or to send resources to offline printers and to create warning notifications regarding the batteries and the printheads status, you can use the cron file which creates a Windows task schedule that runs every hour and works in the background. All these tasks are performed by the “cron.php” file, but the task scheduler can be automatically created using the “task_schedule_creator.php” which will create a Windows task that will run the cron.php file every 60 minutes. 
Assuming you're using XAMPP on a Windows computer and the htdocs folder contained in XAMPP as main folder for the ZPMT files, what you've to do is just enter in your browser the following url http://localhost/task_schedule_creator.php 
On a Linux computer, you'll need to use the crontab to create the task.

INSTALLATION
The main folder contains the empty databse dump that can be imported into the MySQL instance, and the task_schedule_creator.php that can be opened in a browser to automatically create a Windows task to run the cron.php file every 60 minutes.

DESCRIPTION
The homepage shows the list of printers. ![immagine](https://user-images.githubusercontent.com/67392171/230573497-a501696e-a35c-4e20-9469-856192187a63.png)
The background color changes according to the printer status: green if the printer is ready, yellow if the printer is in warning or pause status, red if the printer is in error and gray if the printer is offline. Cliking on the printer name will open the printer details page where the printer configuration and a troubleshooting tab will be shown. 
The printer details page has 4 different self-explanatory tabs:
- General info: this tab shows some basic printer information like firmware version, assigned groups and assigned medias. It also shows the log, the files stored on the printer memory and the odometers. ![immagine](https://user-images.githubusercontent.com/67392171/230573638-83ec9da0-cfd7-4280-a015-71599f1a0f9c.png)

- Configuration tab: this tab is used to show, edit and save all the settings related to the printing, like speed, darkness, device language and so on. This configurations can be saved as .zpl file, which becomes a resource. ![immagine](https://user-images.githubusercontent.com/67392171/230573777-babd5d83-0dc7-4fcb-9caf-675c34e65510.png)

- Network tab: this tab shows all the configuration related to ethernet, wireless, bluetooth and serial communication. Likewise the previous tab, all these settings can be changed or saved as a .zpl file, which becomes a resource. ![immagine](https://user-images.githubusercontent.com/67392171/230573834-c51fb960-0bd5-43f3-85dd-7c51526d37b9.png)

- Troubleshooting tab: this tab can be used to troubleshoot issues. There are buttons that can be used to print a configuration label, to factory default or calibrate the printer. It is also possible to send raw data, to save a printer report and view it, enable or disable the input capture feature to save and analyze the data the printer receives and then to view, download or delete these files. ![immagine](https://user-images.githubusercontent.com/67392171/230573942-7f861f9b-ca3a-4dd5-a068-2a27ebd256cd.png)

The Fleet menu can be used to manage the fleet. In the "add printer" page, given the first and last IP addresses, you can scan a network or you can add a single printer by IP. It is also possible to upload a CSV file which can contain only the IP addresses, the IP addresses and the hostname you want to assign to the printer or the IP addresses, the hostname and the group ID. ![immagine](https://user-images.githubusercontent.com/67392171/230574007-2ec3ebd7-14b6-4d67-b21e-3aa92b23c98c.png)

The "delete printer" page list all the printers and allows to delete the printers from the database. ![immagine](https://user-images.githubusercontent.com/67392171/230575723-46ba7cee-f7b7-4ca1-aaaa-df86b06e53df.png)

The Group menu can be used to manage the groups. In the "manage groups" it is possible to create or cancel a group, but also to view all the printers assigned to a group. ![immagine](https://user-images.githubusercontent.com/67392171/230575855-41b03fbf-22f0-4767-84c5-e0323f948b72.png)

In the "printer assignment to groups" page it is possible to assign or unassign the printer to a group. ![immagine](https://user-images.githubusercontent.com/67392171/230575891-19f36f6f-f8ab-4acb-9c5f-185eb97fb567.png)


The Media menu can be used to manage labels and ribbons. The "manage media" page can be used to insert labels and ribbons in the inventory, by adding all the media features and the amount of rolls available in the inventory. This is required to keep track of the media usage and it's the number or rolls the cron.php file checks against the amount of media used by the printer assigned to the specific label or ribbon. This page also shows the already added medias and clicking on the media name will show the list of printers assigned to it. ![immagine](https://user-images.githubusercontent.com/67392171/230575987-42b87f43-69e7-4d6c-951c-0adc8841bfdd.png)

The "assign printer to paper" and "assign printer to ribbon" pages can be used to assign single or multiple printers to a media. This is required to keep track of the media usage and when a printer is unassigned to a media, the odometers the cron.php file uses to check the amount of media used, is reset to zero. ![immagine](https://user-images.githubusercontent.com/67392171/230576049-ba585fa0-f760-4704-bffa-ff585f4078d4.png) ![immagine](https://user-images.githubusercontent.com/67392171/230576062-f3c7affa-0375-462b-acc0-0873898622c6.png)

The Resource menu can be used to manage the resources files. In the "manage resource" page are listed all the configuration files you can save in the printer details page, but it is also possible to upload custom files, firmware included. The printer and network configuration files can be opened to see their content. ![immagine](https://user-images.githubusercontent.com/67392171/230576120-107686fe-b16c-40eb-84b0-b7f2469df945.png)

The "send resource" page lists the resources file divided by type and the groups, highlighting if the group is empty or if there are offline printers belonging to the group. When this happens, it is still possible to send the resource to the printers and, for the printer offline, this will generate a task for the cron.php file to push the file to the printer as soon as it will be back online. ![immagine](https://user-images.githubusercontent.com/67392171/230576210-877182a0-8088-46ee-9fa7-e83050517551.png)

The "notification" tab shows the results of the battery and the printhead checks the cron.php file regularily performs. ![immagine](https://user-images.githubusercontent.com/67392171/230576252-648d2399-967f-4c28-b0ff-955b474daff5.png)
