/*******************************************************************************

    Copyright 2011 Whole Foods Co-op

    This file is part of IS4C.

    IS4C is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    IS4C is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    in the file license.txt along with IS4C; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*********************************************************************************/

/*************************************************************
 * Magellan
 * 	
 * Wraps Magellan in a Windows Service.
 * Use installutil.exe posSVC.exe to install. When prompted
 * for a username and password, a fully qualified name
 * (i.e., %COMPUTER%\%USER%) is required.
 *
*************************************************************/
using System;
using System.ServiceProcess;
using System.Configuration.Install;
using System.ComponentModel;

using SPH;

public class MagellanWinSVC : ServiceBase {

	protected Magellan my_obj;

	public MagellanWinSVC(){
		this.ServiceName = "IS4C Scale Monitor";
	}

	override protected void OnStart(String[] args){
		this.my_obj = new Magellan();
	}

	override protected void OnStop(){
		if (this.my_obj != null)
			this.my_obj.ShutDown();
	}

	public static void Main(){
		ServiceBase.Run(new MagellanWinSVC());
	}
}

[RunInstallerAttribute(true)]
public class MyInstaller : ServiceProcessInstaller {
	private ServiceInstaller s;

	public MyInstaller(){
		this.s = new ServiceInstaller();	
		this.s.ServiceName = "IS4C Scale Monitor";
		this.Installers.AddRange(new Installer[] {
			this.s
		});
	}	
}
